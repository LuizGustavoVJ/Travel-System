#!/bin/bash
set -e

echo "🚀 Iniciando setup automático do Travel System..."

# Função para descobrir o gateway do Docker automaticamente
get_docker_gateway() {
    # Tenta várias formas de descobrir o gateway
    GATEWAY=""

    # Método 1: ip route (Linux/Mac)
    GATEWAY=$(ip route show default 2>/dev/null | awk '/default/ {print $3}' | head -1 || echo "")

    # Método 2: getent hosts (resolve host.docker.internal)
    if [ -z "$GATEWAY" ]; then
        GATEWAY=$(getent hosts host.docker.internal 2>/dev/null | awk '{print $1}' | head -1 || echo "")
    fi

    # Método 3: hostname -i e calcula gateway (último recurso)
    if [ -z "$GATEWAY" ]; then
        HOST_IP=$(hostname -i 2>/dev/null | awk '{print $1}' || echo "")
        if [ -n "$HOST_IP" ]; then
            # Pega os 3 primeiros octetos e adiciona .1
            GATEWAY=$(echo "$HOST_IP" | cut -d'.' -f1-3).1
        fi
    fi

    # Se ainda não encontrou, retorna vazio (vai tentar só host.docker.internal)
    echo "$GATEWAY"
}

# Função para testar conexão MySQL
test_mysql_connection() {
    local HOST=$1
    local PORT=$2
    local USER=$3
    local PASS=$4

    if [ -z "$PASS" ]; then
        mysql -h"$HOST" -P"$PORT" -u"$USER" --skip-ssl -e "SELECT 1" 2>/dev/null
    else
        mysql -h"$HOST" -P"$PORT" -u"$USER" -p"$PASS" --skip-ssl -e "SELECT 1" 2>/dev/null
    fi
}

# Função para aguardar MySQL estar disponível
wait_for_mysql() {
    echo "⏳ Aguardando MySQL estar disponível..."

    DB_HOST=${DB_HOST:-host.docker.internal}
    DB_PORT=${DB_PORT:-3306}
    DB_USERNAME=${DB_USERNAME:-root}
    DB_PASSWORD=${DB_PASSWORD:-}

    MAX_RETRIES=15
    RETRY_COUNT=0
    CONNECTED=false

    # Lista de hosts para tentar (em ordem de prioridade)
    # 1. Tenta MySQL local primeiro (host.docker.internal na porta 3306)
    # 2. Se não conseguir, tenta MySQL do Docker (db na porta 3306)
    HOSTS_TO_TRY=("host.docker.internal" "db")

    # Descobre gateway automaticamente e adiciona à lista (para casos especiais)
    GATEWAY=$(get_docker_gateway)
    if [ -n "$GATEWAY" ] && [ "$GATEWAY" != "host.docker.internal" ] && [ "$GATEWAY" != "db" ]; then
        HOSTS_TO_TRY+=("$GATEWAY")
        echo "🔍 Gateway descoberto: $GATEWAY"
    fi

    echo "🔍 Ordem de tentativas: ${HOSTS_TO_TRY[*]}"
    echo "   - host.docker.internal: MySQL local (se disponível)"
    echo "   - db: MySQL do Docker (fallback automático)"

    # Tenta cada host na lista
    for CURRENT_HOST in "${HOSTS_TO_TRY[@]}"; do
        if [ "$CONNECTED" = true ]; then
            break
        fi

        echo "🔍 Tentando conectar via: $CURRENT_HOST"
        RETRY_COUNT=0

        until test_mysql_connection "$CURRENT_HOST" "$DB_PORT" "$DB_USERNAME" "$DB_PASSWORD" || [ $RETRY_COUNT -eq $MAX_RETRIES ]; do
            RETRY_COUNT=$((RETRY_COUNT + 1))
            if [ $RETRY_COUNT -lt $MAX_RETRIES ]; then
                echo "⏳ Tentativa $RETRY_COUNT/$MAX_RETRIES - MySQL ainda não está disponível via $CURRENT_HOST. Aguardando..."
                sleep 2
            else
                # Na última tentativa, mostra o erro
                ERROR_MSG=$(test_mysql_connection "$CURRENT_HOST" "$DB_PORT" "$DB_USERNAME" "$DB_PASSWORD" 2>&1 || true)
                echo "❌ Falhou com $CURRENT_HOST: $ERROR_MSG"
            fi
        done

        # Se conseguiu conectar, atualiza DB_HOST
        if test_mysql_connection "$CURRENT_HOST" "$DB_PORT" "$DB_USERNAME" "$DB_PASSWORD" 2>/dev/null; then
            echo "✅ Conectado via $CURRENT_HOST!"
            DB_HOST=$CURRENT_HOST
            export DB_HOST=$CURRENT_HOST
            # Atualiza .env também
            sed -i "s|^DB_HOST=.*|DB_HOST=${CURRENT_HOST}|" /var/www/html/.env 2>/dev/null || sed -i '' "s|^DB_HOST=.*|DB_HOST=${CURRENT_HOST}|" /var/www/html/.env 2>/dev/null || true
            CONNECTED=true
            break
        fi
    done

    if [ "$CONNECTED" = false ]; then
        echo "❌ Erro: Não foi possível conectar ao MySQL após tentar todos os hosts"
        echo "💡 Hosts tentados: ${HOSTS_TO_TRY[*]}"
        echo ""
        echo "💡 O sistema tentou:"
        echo "   1. MySQL local (host.docker.internal:3306) - se você tem MySQL instalado"
        echo "   2. MySQL do Docker (db:3306) - container automático"
        echo ""
        echo "💡 Verifique:"
        echo "   - Se você tem MySQL local, ele está rodando?"
        echo "   - O container MySQL do Docker está rodando? (docker ps | grep mysql)"
        echo "   - Senha está correta? (MYSQL_PASSWORD=${MYSQL_PASSWORD:-vazio})"
        echo "   - MySQL está na porta 3306?"
        return 1
    fi

    echo "✅ MySQL está disponível em $DB_HOST!"
    return 0
}

# Função para criar o schema se não existir
create_database() {
    echo "📦 Verificando se o schema '${DB_DATABASE}' existe..."

    # Usa as variáveis já exportadas (DB_HOST foi descoberto na função wait_for_mysql)
    DB_HOST=${DB_HOST:-host.docker.internal}
    DB_PORT=${DB_PORT:-3306}
    DB_USERNAME=${DB_USERNAME:-root}
    DB_PASSWORD=${DB_PASSWORD:-}
    DB_DATABASE=${DB_DATABASE:-travel_system}

    echo "📦 Usando host: $DB_HOST, database: $DB_DATABASE"

    # Verifica se o schema existe (com ou sem senha, desabilitando SSL)
    if [ -z "$DB_PASSWORD" ]; then
        SCHEMA_EXISTS=$(mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" --skip-ssl -e "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME='${DB_DATABASE}'" 2>/dev/null | grep -c "${DB_DATABASE}" || echo "0")
    else
        SCHEMA_EXISTS=$(mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" --skip-ssl -e "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME='${DB_DATABASE}'" 2>/dev/null | grep -c "${DB_DATABASE}" || echo "0")
    fi

    if [ "$SCHEMA_EXISTS" -eq "0" ]; then
        echo "📦 Criando schema '${DB_DATABASE}'..."
        if [ -z "$DB_PASSWORD" ]; then
            if mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" --skip-ssl -e "CREATE DATABASE IF NOT EXISTS ${DB_DATABASE} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null; then
                echo "✅ Schema '${DB_DATABASE}' criado com sucesso!"
                return 0
            else
                echo "❌ Erro ao criar schema '${DB_DATABASE}'"
                mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" --skip-ssl -e "CREATE DATABASE IF NOT EXISTS ${DB_DATABASE} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>&1 || true
                return 1
            fi
        else
            if mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" --skip-ssl -e "CREATE DATABASE IF NOT EXISTS ${DB_DATABASE} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null; then
                echo "✅ Schema '${DB_DATABASE}' criado com sucesso!"
                return 0
            else
                echo "❌ Erro ao criar schema '${DB_DATABASE}'"
                mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" --skip-ssl -e "CREATE DATABASE IF NOT EXISTS ${DB_DATABASE} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>&1 || true
                return 1
            fi
        fi
    else
        echo "✅ Schema '${DB_DATABASE}' já existe!"
        return 0
    fi
}

# Navega para o diretório da aplicação
cd /var/www/html

# Cria ou ATUALIZA .env (PRIMEIRO, antes de tudo!)
MYSQL_PASS=${MYSQL_PASSWORD:-1012@lg}

if [ ! -f "/var/www/html/.env" ]; then
    echo "📄 Criando arquivo .env..."
    if [ -f "/var/www/html/.env.example" ]; then
        cp /var/www/html/.env.example /var/www/html/.env
        echo "✅ Arquivo .env criado a partir do .env.example!"
    else
        echo "📄 Criando .env com valores padrão..."
        cat > /var/www/html/.env << EOF
APP_NAME=TravelSystem
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=host.docker.internal
DB_PORT=3306
DB_DATABASE=travel_system
DB_USERNAME=root
DB_PASSWORD=${MYSQL_PASS}

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=rabbitmq
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest
RABBITMQ_VHOST=/
RABBITMQ_QUEUE=default
SESSION_DRIVER=file
SESSION_LIFETIME=120

MAIL_MAILER=log
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="\${APP_NAME}"

JWT_SECRET=
EOF
        echo "✅ Arquivo .env criado com valores padrão!"
    fi
fi

# SEMPRE atualiza configurações do MySQL (mesmo se .env já existir)
echo "📄 Atualizando configurações do MySQL no .env..."
sed -i 's/^DB_HOST=.*/DB_HOST=host.docker.internal/' /var/www/html/.env 2>/dev/null || sed -i '' 's/^DB_HOST=.*/DB_HOST=host.docker.internal/' /var/www/html/.env 2>/dev/null || true
sed -i 's/^DB_PORT=.*/DB_PORT=3306/' /var/www/html/.env 2>/dev/null || sed -i '' 's/^DB_PORT=.*/DB_PORT=3306/' /var/www/html/.env 2>/dev/null || true
sed -i 's/^DB_DATABASE=.*/DB_DATABASE=travel_system/' /var/www/html/.env 2>/dev/null || sed -i '' 's/^DB_DATABASE=.*/DB_DATABASE=travel_system/' /var/www/html/.env 2>/dev/null || true
sed -i 's/^DB_USERNAME=.*/DB_USERNAME=root/' /var/www/html/.env 2>/dev/null || sed -i '' 's/^DB_USERNAME=.*/DB_USERNAME=root/' /var/www/html/.env 2>/dev/null || true

# Atualiza ou adiciona DB_PASSWORD
if grep -q "^DB_PASSWORD=" /var/www/html/.env 2>/dev/null; then
    sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${MYSQL_PASS}|" /var/www/html/.env 2>/dev/null || sed -i '' "s|^DB_PASSWORD=.*|DB_PASSWORD=${MYSQL_PASS}|" /var/www/html/.env 2>/dev/null || true
else
    echo "DB_PASSWORD=${MYSQL_PASS}" >> /var/www/html/.env
fi

# Garante que DB_CONNECTION está como mysql
if ! grep -q "^DB_CONNECTION=" /var/www/html/.env 2>/dev/null; then
    sed -i '/^DB_HOST=/i DB_CONNECTION=mysql' /var/www/html/.env 2>/dev/null || sed -i '' '/^DB_HOST=/i DB_CONNECTION=mysql' /var/www/html/.env 2>/dev/null || true
else
    sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=mysql/' /var/www/html/.env 2>/dev/null || sed -i '' 's/^DB_CONNECTION=.*/DB_CONNECTION=mysql/' /var/www/html/.env 2>/dev/null || true
fi

echo "✅ Configurações do MySQL atualizadas no .env!"

# Carrega variáveis do .env (agora que ele existe)
echo "📄 Carregando variáveis do .env..."
set -a
source /var/www/html/.env
set +a

# Define valores padrão (caso não estejam no .env)
export DB_HOST=${DB_HOST:-host.docker.internal}
export DB_PORT=${DB_PORT:-3306}
export DB_DATABASE=${DB_DATABASE:-travel_system}
export DB_USERNAME=${DB_USERNAME:-root}
# Usa MYSQL_PASSWORD do ambiente se DB_PASSWORD não estiver no .env
export DB_PASSWORD=${DB_PASSWORD:-${MYSQL_PASSWORD:-1012@lg}}

# Instala dependências do Composer se necessário (ANTES de conectar ao MySQL)
if [ ! -d "/var/www/html/vendor" ]; then
    echo "📦 Instalando dependências do Composer..."
    if composer install --no-interaction --prefer-dist --optimize-autoloader; then
        echo "✅ Dependências instaladas!"
    else
        echo "⚠️ Erro ao instalar dependências. Tentando continuar..."
        # Tenta instalar ignorando requisitos de plataforma se necessário
        composer install --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-req=ext-sockets || true
    fi
else
    echo "✅ Dependências já instaladas!"
fi

# Aguarda MySQL estar disponível e cria o schema (OBRIGATÓRIO antes das migrations)
SCHEMA_CREATED=false

if wait_for_mysql; then
    # Cria o schema se não existir
    if create_database; then
        SCHEMA_CREATED=true
        echo "✅ Schema criado/verificado com sucesso!"
    else
        echo "⚠️ Erro ao criar schema na primeira tentativa."
    fi
fi

# Se não conseguiu criar, tenta novamente antes das migrations
if [ "$SCHEMA_CREATED" = false ]; then
    echo ""
    echo "🔄 Tentando conectar ao MySQL novamente antes das migrations..."
    sleep 3
    if wait_for_mysql; then
        if create_database; then
            SCHEMA_CREATED=true
            echo "✅ Schema criado com sucesso na segunda tentativa!"
        fi
    fi
fi

# Verifica se o schema foi criado antes de continuar
if [ "$SCHEMA_CREATED" = false ]; then
    echo ""
    echo "❌ ERRO CRÍTICO: Não foi possível criar o schema 'travel_system'!"
    echo ""
    echo "💡 AÇÕES NECESSÁRIAS:"
    echo "   1. Verifique se o MySQL está rodando localmente"
    echo "   2. Verifique se a senha está correta (MYSQL_PASSWORD no docker-compose.yml)"
    echo "   3. Crie o schema manualmente no MySQL Workbench:"
    echo "      CREATE DATABASE IF NOT EXISTS travel_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    echo ""
    echo "   4. Depois, execute novamente: docker-compose restart app"
    echo ""
    exit 1
fi

# Gera chave da aplicação se não existir ou estiver vazia
if ! grep -q "^APP_KEY=base64:" /var/www/html/.env 2>/dev/null; then
    echo "🔑 Gerando chave da aplicação..."
    php artisan key:generate --force
    echo "✅ Chave gerada!"
else
    echo "✅ Chave da aplicação já existe!"
fi

# Gera chave JWT se não existir
if ! grep -q "^JWT_SECRET=" /var/www/html/.env 2>/dev/null || grep -q "^JWT_SECRET=$" /var/www/html/.env 2>/dev/null; then
    echo "🔑 Gerando chave JWT..."
    php artisan jwt:secret --force
    echo "✅ Chave JWT gerada!"
else
    echo "✅ Chave JWT já existe!"
fi

# Verifica se deve pular migrations/seeders (para worker e scheduler)
if [ "${SKIP_MIGRATIONS:-false}" = "true" ]; then
    echo "⏭️  Pulando migrations/seeders (SKIP_MIGRATIONS=true)"
    echo "⏳ Aguardando apenas que o banco esteja pronto..."

    # Aguarda até que as tabelas existam (aguarda o app terminar o setup)
    MAX_WAIT=60
    WAIT_COUNT=0
    TABLES_READY=false

    while [ $WAIT_COUNT -lt $MAX_WAIT ] && [ "$TABLES_READY" = false ]; do
        TABLE_COUNT=$(mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" --skip-ssl -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '${DB_DATABASE}'" 2>/dev/null | tail -1 || echo "0")

        if [ "$TABLE_COUNT" -gt "5" ]; then
            echo "✅ Tabelas prontas! ($TABLE_COUNT tabelas encontradas)"
            TABLES_READY=true
            break
        else
            WAIT_COUNT=$((WAIT_COUNT + 1))
            if [ $((WAIT_COUNT % 5)) -eq 0 ]; then
                echo "⏳ Aguardando tabelas serem criadas... ($WAIT_COUNT/$MAX_WAIT)"
            fi
            sleep 2
        fi
    done

    if [ "$TABLES_READY" = false ]; then
        echo "⚠️  Aviso: Tabelas ainda não estão prontas após $MAX_WAIT tentativas"
        echo "💡 O container 'app' pode ainda estar executando migrations..."
    fi
else
    # Executa migrations (cria todas as tabelas) - APENAS NO CONTAINER APP
    echo "🗄️ Verificando tabelas existentes no banco de dados..."

    # Conta quantas tabelas existem no schema
    TABLE_COUNT=$(mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" --skip-ssl -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '${DB_DATABASE}'" 2>/dev/null | tail -1 || echo "0")

    if [ "$TABLE_COUNT" -gt "0" ]; then
        echo "⚠️ Encontradas $TABLE_COUNT tabela(s) no banco de dados."
        echo "🗑️ Apagando todas as tabelas existentes para recriar do zero..."
        # Usa migrate:fresh que apaga todas as tabelas e recria
        if php artisan migrate:fresh --force; then
            echo "✅ Todas as tabelas foram recriadas com sucesso!"
        else
            echo "❌ Erro ao recriar tabelas!"
            echo "💡 Verifique se:"
            echo "   1. O schema 'travel_system' foi criado"
            echo "   2. O MySQL está acessível"
            echo "   3. As credenciais estão corretas no .env"
            exit 1
        fi
    else
        echo "📦 Nenhuma tabela encontrada. Criando todas as tabelas..."
        if php artisan migrate --force; then
            echo "✅ Migrations executadas! Tabelas criadas com sucesso!"
        else
            echo "❌ Erro ao executar migrations!"
            echo "💡 Verifique se:"
            echo "   1. O schema 'travel_system' foi criado"
            echo "   2. O MySQL está acessível"
            echo "   3. As credenciais estão corretas no .env"
            exit 1
        fi
    fi

    # Executa seeders (sempre, para garantir dados iniciais)
    echo "🌱 Executando seeders..."
    php artisan db:seed --force
    echo "✅ Seeders executados!"

    # Limpa cache (apenas após executar migrations/seeders)
    echo "🧹 Limpando cache..."
    php artisan config:clear
    php artisan cache:clear
    php artisan route:clear
    php artisan view:clear
    echo "✅ Cache limpo!"
fi

echo ""
echo "✅✅✅ Setup completo! Sistema pronto para uso! ✅✅✅"
echo ""

# Executa o comando passado como argumento (php-fpm por padrão)
exec "$@"


#!/bin/bash
set -e

echo "🚀 Iniciando setup do Travel System..."

# Função para aguardar MySQL estar disponível
wait_for_mysql() {
    echo "⏳ Aguardando MySQL estar disponível..."
    
    DB_HOST=${DB_HOST:-host.docker.internal}
    DB_PORT=${DB_PORT:-3306}
    DB_USERNAME=${DB_USERNAME:-root}
    DB_PASSWORD=${DB_PASSWORD:1012@lg}
    DB_DATABASE=${DB_DATABASE:-travel_system}
    
    until mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "SELECT 1" &> /dev/null; do
        echo "⏳ MySQL ainda não está disponível. Aguardando..."
        sleep 2
    done
    
    echo "✅ MySQL está disponível!"
}

# Função para criar o schema se não existir
create_database() {
    echo "📦 Verificando se o schema '${DB_DATABASE}' existe..."
    
    DB_HOST=${DB_HOST:-host.docker.internal}
    DB_PORT=${DB_PORT:-3306}
    DB_USERNAME=${DB_USERNAME:-root}
    DB_PASSWORD=${DB_PASSWORD:1012@lg}
    DB_DATABASE=${DB_DATABASE:-travel_system}
    
    # Verifica se o schema existe
    SCHEMA_EXISTS=$(mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME='${DB_DATABASE}'" 2>/dev/null | grep -c "${DB_DATABASE}" || echo "0")
    
    if [ "$SCHEMA_EXISTS" -eq "0" ]; then
        echo "📦 Criando schema '${DB_DATABASE}'..."
        mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS ${DB_DATABASE} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null
        echo "✅ Schema '${DB_DATABASE}' criado com sucesso!"
    else
        echo "✅ Schema '${DB_DATABASE}' já existe!"
    fi
}

# Carrega variáveis do .env se existir
if [ -f /var/www/html/.env ]; then
    export $(cat /var/www/html/.env | grep -v '^#' | xargs)
fi

# Aguarda MySQL estar disponível
wait_for_mysql

# Cria o schema se não existir
create_database

# Instala dependências do Composer se necessário
if [ ! -d "/var/www/html/vendor" ]; then
    echo "📦 Instalando dependências do Composer..."
    cd /var/www/html
    composer install --no-interaction --prefer-dist --optimize-autoloader
    echo "✅ Dependências instaladas!"
fi

# Gera chave da aplicação se não existir
if [ -z "$APP_KEY" ] || [ "$APP_KEY" == "" ]; then
    echo "🔑 Gerando chave da aplicação..."
    cd /var/www/html
    php artisan key:generate --force
    echo "✅ Chave gerada!"
fi

# Gera chave JWT se não existir
if ! grep -q "JWT_SECRET" /var/www/html/.env 2>/dev/null || grep -q "JWT_SECRET=" /var/www/html/.env 2>/dev/null && ! grep -q "JWT_SECRET=[a-zA-Z0-9]" /var/www/html/.env 2>/dev/null; then
    echo "🔑 Gerando chave JWT..."
    cd /var/www/html
    php artisan jwt:secret --force
    echo "✅ Chave JWT gerada!"
fi

# Executa migrations
echo "🗄️ Executando migrations..."
cd /var/www/html
php artisan migrate --force

# Executa seeders apenas se não houver dados
USER_COUNT=$(php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null || echo "0")
if [ "$USER_COUNT" -eq "0" ]; then
    echo "🌱 Executando seeders..."
    php artisan db:seed --force
    echo "✅ Seeders executados!"
else
    echo "ℹ️ Dados já existem no banco. Pulando seeders."
fi

echo "✅ Setup concluído! Iniciando servidor..."

# Executa o comando passado como argumento (php-fpm por padrão)
exec "$@"


#!/bin/bash
# ============================================
# Script para executar testes automatizados
# ============================================

set -e

echo "🧪 Executando testes automatizados do Travel System..."
echo ""

# Cores para output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Verifica se os containers estão rodando
if ! docker-compose ps | grep -q "travel-system-db.*Up"; then
    echo -e "${YELLOW}⚠️  Containers não estão rodando. Iniciando containers necessários...${NC}"
    docker-compose up -d db redis
    echo "⏳ Aguardando serviços ficarem prontos..."
    sleep 10
fi

# Opções de execução
TEST_SUITE="${1:-all}"
TEST_FILTER="${2:-}"

case "$TEST_SUITE" in
    "unit")
        echo -e "${BLUE}📦 Executando testes Unit...${NC}"
        docker-compose run --rm --profile test phpunit vendor/bin/phpunit --testsuite=Unit --colors=always $TEST_FILTER
        ;;
    "feature")
        echo -e "${BLUE}📦 Executando testes Feature...${NC}"
        docker-compose run --rm --profile test phpunit vendor/bin/phpunit --testsuite=Feature --colors=always $TEST_FILTER
        ;;
    "coverage")
        echo -e "${BLUE}📊 Executando testes com coverage...${NC}"
        docker-compose run --rm --profile test phpunit vendor/bin/phpunit --coverage-html coverage --colors=always
        echo -e "${GREEN}✅ Coverage gerado em: coverage/index.html${NC}"
        ;;
    "filter")
        if [ -z "$TEST_FILTER" ]; then
            echo -e "${YELLOW}⚠️  Especifique o filtro: ./run-tests.sh filter TestClassName${NC}"
            exit 1
        fi
        echo -e "${BLUE}🔍 Executando testes filtrados: $TEST_FILTER${NC}"
        docker-compose run --rm --profile test phpunit vendor/bin/phpunit --filter="$TEST_FILTER" --colors=always
        ;;
    "all"|*)
        echo -e "${BLUE}🚀 Executando TODOS os testes...${NC}"
        docker-compose run --rm --profile test phpunit vendor/bin/phpunit --colors=always
        ;;
esac

echo ""
echo -e "${GREEN}✅ Testes concluídos!${NC}"


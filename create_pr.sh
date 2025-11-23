#!/bin/bash

echo "=== INSTRUÇÕES PARA CRIAR O PULL REQUEST ==="
echo ""
echo "1. Faça login no GitHub CLI:"
echo "   gh auth login"
echo ""
echo "2. Faça push do branch:"
echo "   git push origin feature/implementacao-completa-frontend"
echo ""
echo "3. Crie o Pull Request:"
echo "   gh pr create --title \"[FEATURE] Implementação Completa do Sistema de Gerenciamento de Viagens\" --body-file PR_DESCRIPTION.md --base main --head feature/implementacao-completa-frontend"
echo ""
echo "Ou acesse: https://github.com/LuizGustavoVJ/Travel-System/compare/main...feature/implementacao-completa-frontend"
echo ""
echo "=== DESCRIÇÃO DO PR ==="
cat PR_DESCRIPTION.md

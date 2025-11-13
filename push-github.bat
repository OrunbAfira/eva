@echo off
echo ========================================
echo  PUSH PARA GITHUB - Plataforma EVA
echo ========================================
echo.

cd /d "c:\xampp\htdocs\Summit"

echo [1/5] Verificando status do repositorio...
git status

echo.
echo [2/5] Adicionando todos os arquivos...
git add .

echo.
echo [3/5] Criando commit...
git commit -m "feat: Atualização da plataforma EVA - correção encoding data e melhorias"

echo.
echo [4/5] Fazendo push forçado para o GitHub...
echo ATENCAO: Isso vai substituir todo o conteudo remoto!
pause

git push -f origin main

echo.
echo [5/5] Concluido!
echo.
echo Acesse: https://github.com/OrunbAfira/eva
echo.
pause

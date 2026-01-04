#!/bin/bash

echo " Iniciando deploy do projeto..."

./vendor/bin/pint

git add .
git commit -m "update"
git push

echo " Deploy concluído com sucesso!"

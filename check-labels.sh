#!/bin/bash

# Cria pasta temporária para análise
mkdir -p /tmp/label-check

echo "Buscando arquivos Blade com <label for=...>"
find resources/views -type f -name "*.blade.php" -exec grep -l "<label.*for=" {} \; > /tmp/label-check/label_files.txt

echo "Analisando arquivos encontrados..."
for file in $(cat /tmp/label-check/label_files.txt); do
  echo "Verificando $file"
  
  # Extrai todos os valores de for= dos labels
  grep -o '<label[^>]*for="[^"]*"' "$file" | sed 's/.*for="\([^"]*\)".*/\1/' > /tmp/label-check/for_values.txt
  
  # Extrai todos os valores de id= dos inputs/selects/textareas
  grep -o '<\(input\|select\|textarea\)[^>]*id="[^"]*"' "$file" | sed 's/.*id="\([^"]*\)".*/\1/' > /tmp/label-check/id_values.txt
  
  # Compara e encontra labels sem ids correspondentes
  echo "Labels sem IDs correspondentes em $file:" > /tmp/label-check/missing_ids.txt
  while read for_value; do
    if ! grep -q "^$for_value$" /tmp/label-check/id_values.txt; then
      echo "  - Label for=\"$for_value\" não tem input com id=\"$for_value\"" >> /tmp/label-check/missing_ids.txt
      # Mostra a linha completa do label com o for problemático
      grep -n "for=\"$for_value\"" "$file" | head -1 >> /tmp/label-check/missing_ids.txt
    fi
  done < /tmp/label-check/for_values.txt
  
  # Exibe resultados se houver problemas
  if [ -s /tmp/label-check/missing_ids.txt ]; then
    cat /tmp/label-check/missing_ids.txt
    echo ""
  fi
done

# Verifica se o DOCTYPE está presente nos layouts principais
echo "Verificando DOCTYPE nos layouts principais..."
for layout in resources/views/layouts/*.blade.php; do
  if [ -f "$layout" ]; then
    if ! grep -q "<!DOCTYPE html>" "$layout"; then
      echo "AVISO: $layout não tem <!DOCTYPE html> no início do arquivo"
    fi
  fi
done

echo "Verificação concluída!"

import os
import re

base_dir = r"C:\Users\Ing. Joan Sanchez\Documents\GitHub\jaryelm.github.io\MEDIDATA_Lab_Serv"

files_to_update = [
    r"frontend\recursos_humanos\lista_colaboradores.php",
    r"frontend\recursos_humanos\lista_excolaboradores.php",
    r"frontend\recursos_humanos\administrativo.php",
    r"frontend\recursos_humanos\administrativo_ex.php",
    r"frontend\recursos_humanos\servicios_generales.php",
    r"frontend\recursos_humanos\servicios_generales_ex.php",
    r"frontend\medicos\mostrar.php",
    r"frontend\medicos\mostrar_ex.php"
]

search_pattern = re.compile(
    r'(<\?php if \(!empty\(\$d->url_contrato\)\): \?>\s*<a href="[^"]+" target="_blank" class="badge-success"[^>]*><i class="bx bx-file"></i> Ver</a>)(\s*<\?php else: \?>)',
    re.MULTILINE
)

replace_text = r'\1\n                                            <a href="#" onclick="deleteContract(<?php echo $d->id; ?>, \'<?php echo htmlspecialchars($d->source_table); ?>\', \'<?php echo htmlspecialchars($d->source_idcol); ?>\'); return false;" class="badge-danger" style="padding:4px; text-decoration:none; margin-left:4px;" title="Eliminar contrato"><i class="bx bx-trash"></i></a>\2'

for f in files_to_update:
    path = os.path.join(base_dir, f)
    if not os.path.exists(path):
        continue
        
    with open(path, "r", encoding="utf-8") as file:
        content = file.read()
        
    new_content, count = search_pattern.subn(replace_text, content)
    
    if count > 0:
        with open(path, "w", encoding="utf-8") as file:
            file.write(new_content)
        print(f"Updated {f} (found {count} matches)")
    else:
        # Check for alternative pattern in other files
        alt_pattern = re.compile(
            r'(<\?php if \(!empty\(\$d->url_contrato\)\): \?>\s*<a href="[^"]+" target="_blank" class="badge-success"[^>]*><i class="bx bx-file"></i> Ver</a>)(\s*<\?php else: \?>)',
            re.MULTILINE
        )
        print(f"No match found in {f}")

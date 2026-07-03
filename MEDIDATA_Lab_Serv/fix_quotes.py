import os
import re

base_dir = r"C:\Users\Ing. Joan Sanchez\Documents\GitHub\jaryelm.github.io\MEDIDATA_Lab_Serv"

files_to_update = [
    r"frontend\recursos_humanos\lista_colaboradores.php",
    r"frontend\recursos_humanos\lista_excolaboradores.php",
    r"frontend\recursos_humanos\administrativo.php",
    r"frontend\recursos_humanos\servicios_generales.php",
    r"frontend\medicos\mostrar.php"
]

for f in files_to_update:
    path = os.path.join(base_dir, f)
    if not os.path.exists(path):
        continue
        
    with open(path, "r", encoding="utf-8") as file:
        content = file.read()
        
    new_content = content.replace(r"\'<?php echo htmlspecialchars($d->source_table); ?>\'", r"'<?php echo htmlspecialchars($d->source_table); ?>'")
    new_content = new_content.replace(r"\'<?php echo htmlspecialchars($d->source_idcol); ?>\'", r"'<?php echo htmlspecialchars($d->source_idcol); ?>'")
    
    with open(path, "w", encoding="utf-8") as file:
        file.write(new_content)
    print(f"Fixed quotes in {f}")

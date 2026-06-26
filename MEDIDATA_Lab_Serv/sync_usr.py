import os

base_dir = r"C:\Users\Ing. Joan Sanchez\Documents\GitHub\jaryelm.github.io\MEDIDATA_Lab_Serv"

files_to_sync = [
    (r"frontend\recursos_humanos\lista_colaboradores.php", r"frontend\recursos_humanos\lista_colaboradores_usr.php"),
    (r"frontend\recursos_humanos\lista_excolaboradores.php", r"frontend\recursos_humanos\lista_excolaboradores_usr.php"),
    (r"frontend\recursos_humanos\administrativo.php", r"frontend\recursos_humanos\administrativo_usr.php"),
    (r"frontend\recursos_humanos\administrativo_ex.php", r"frontend\recursos_humanos\administrativo_ex_usr.php"),
    (r"frontend\recursos_humanos\servicios_generales.php", r"frontend\recursos_humanos\servicios_generales_usr.php"),
    (r"frontend\recursos_humanos\servicios_generales_ex.php", r"frontend\recursos_humanos\servicios_generales_ex_usr.php"),
    (r"frontend\medicos\mostrar.php", r"frontend\medicos\mostrar_usr.php"),
    (r"frontend\medicos\mostrar_ex.php", r"frontend\medicos\mostrar_ex_usr.php")
]

for src, dst in files_to_sync:
    src_path = os.path.join(base_dir, src)
    dst_path = os.path.join(base_dir, dst)
    
    if not os.path.exists(src_path):
        print(f"Skipping {src}, not found.")
        continue
        
    with open(src_path, "r", encoding="utf-8") as f:
        content = f.read()
        
    if 'recursos_humanos' in src:
        # Replace menu and perfil inclusions
        content = content.replace("include_once '../admin/menu.php';", "include_once './menu.php';")
        content = content.replace("include_once '../admin/perfil.php';", "include_once './perfil.php';")
    
    # Replace tab links in recursos humanos
    content = content.replace('href="lista_colaboradores.php"', 'href="lista_colaboradores_usr.php"')
    content = content.replace('href="lista_colaboradores_medicos.php"', 'href="lista_colaboradores_medicos_usr.php"')
    content = content.replace('href="lista_excolaboradores.php"', 'href="lista_excolaboradores_usr.php"')
    
    # Replace button links for administrativo
    content = content.replace("'administrativo.php'", "'administrativo_usr.php'")
    content = content.replace("'administrativo_ex.php'", "'administrativo_ex_usr.php'")
    content = content.replace("'administrativo_nuevo.php'", "'administrativo_nuevo_usr.php'")
    
    # Replace button links for servicios_generales
    content = content.replace("'servicios_generales.php'", "'servicios_generales_usr.php'")
    content = content.replace("'servicios_generales_ex.php'", "'servicios_generales_ex_usr.php'")
    content = content.replace("'servicios_generales_nuevo.php'", "'servicios_generales_nuevo_usr.php'")
    
    # Replace edit links inside the PHP query string for union
    content = content.replace("'administrativo_editar.php' AS edit_file", "'administrativo_editar_usr.php' AS edit_file")
    content = content.replace("'servicios_generales_editar.php' AS edit_file", "'servicios_generales_editar_usr.php' AS edit_file")
    
    # Replace edit links in standard PHP loops
    content = content.replace('href="administrativo_editar.php?id=', 'href="administrativo_editar_usr.php?id=')
    content = content.replace('href="servicios_generales_editar.php?id=', 'href="servicios_generales_editar_usr.php?id=')

    with open(dst_path, "w", encoding="utf-8") as f:
        f.write(content)
        
    print(f"Synced {src} -> {dst}")

import re

def process_file(filepath, is_admin):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # 1. Menu include block
    menu_block_pattern = r'<\?php\s*if\s*\(isset\(\\[\'rol\'\]\)\s*&&\s*\\[\'rol\'\]\s*==\s*\'Administrador\'\)\s*\{\s*include_once\s*\'\.\./admin/menu\.php\';\s*\}\s*else\s*\{\s*include_once\s*\'\.\./recursos_humanos/menu\.php\';\s*\}\s*\?>'
    
    if is_admin:
        content = re.sub(menu_block_pattern, "<?php include_once '../admin/menu.php'; ?>", content, flags=re.DOTALL)
    else:
        content = re.sub(menu_block_pattern, "<?php include_once '../recursos_humanos/menu.php'; ?>", content, flags=re.DOTALL)
        
    # 2. "Agregar Colaborador" button
    btn_pattern = r'<\?php\s*if\s*\(isset\(\\[\'rol\'\]\)\s*&&\s*\\[\'rol\'\]\s*==\s*\'Administrador\'\):\s*\?>\s*<a\s*href="agregar_colaborador\.php"[^>]*>Agregar Colaborador</a>\s*<\?php\s*endif;\s*\?>'
    if is_admin:
        content = re.sub(btn_pattern, '<a href="agregar_colaborador.php" class="button tab-button" style="background-color: #28a745; color: white;">Agregar Colaborador</a>', content)
    else:
        content = re.sub(btn_pattern, '', content)

    # 3. Contenteditable cells (admin only gets contenteditable)
    editable_pattern = r'<\?php\s*echo\s*\(isset\(\\[\'rol\'\]\)\s*&&\s*\\[\'rol\'\]\s*==\s*\'Administrador\'\)\s*\?\s*\'class="editable-cell" contenteditable="true"\'\s*:\s*\'\';\s*\?>'
    if is_admin:
        content = re.sub(editable_pattern, 'class="editable-cell" contenteditable="true"', content)
    else:
        content = re.sub(editable_pattern, '', content)

    # 4. Inline Selects disabled attribute
    disabled_pattern = r'<\?php\s*echo\s*\(isset\(\\[\'rol\'\]\)\s*&&\s*\\[\'rol\'\]\s*\!=\s*\'Administrador\'\)\s*\?\s*\'disabled\'\s*:\s*\'\';\s*\?>'
    if is_admin:
        content = re.sub(disabled_pattern, '', content)
    else:
        content = re.sub(disabled_pattern, 'disabled', content)

    # 5. Delete contract button (admin only)
    del_contract_pattern = r'<\?php\s*if\s*\(isset\(\\[\'rol\'\]\)\s*&&\s*\\[\'rol\'\]\s*==\s*\'Administrador\'\):\s*\?>\s*<a[^>]*class="badge-danger"[^>]*>.*?</a>\s*<\?php\s*endif;\s*\?>'
    if is_admin:
        content = re.sub(del_contract_pattern, lambda m: re.sub(r'<\?php.*?:\s*\?>|(?<=</a>)\s*<\?php\s*endif;\s*\?>', '', m.group(0)), content, flags=re.DOTALL)
    else:
        content = re.sub(del_contract_pattern, '', content, flags=re.DOTALL)

    # 6. Upload contract block
    upload_pattern = r'<\?php\s*if\s*\(isset\(\\[\'rol\'\]\)\s*&&\s*\\[\'rol\'\]\s*==\s*\'Administrador\'\):\s*\?>\s*<br>\s*<label[^>]*>.*?<input[^>]*>\s*<\?php\s*endif;\s*\?>'
    if is_admin:
        content = re.sub(upload_pattern, lambda m: re.sub(r'<\?php.*?:\s*\?>|(?<=<input).*?>\s*<\?php\s*endif;\s*\?>', '', m.group(0), flags=re.DOTALL) + '>', content, flags=re.DOTALL)
    else:
        content = re.sub(upload_pattern, '', content, flags=re.DOTALL)

    # 7. Edit link column (admin only)
    edit_link_pattern = r'<\?php\s*if\s*\(isset\(\\[\'rol\'\]\)\s*&&\s*\\[\'rol\'\]\s*==\s*\'Administrador\'\):\s*\?>\s*<a\s*title="Actualizar"[^>]*></a>\s*<\?php\s*endif;\s*\?>'
    if is_admin:
        content = re.sub(edit_link_pattern, lambda m: re.sub(r'<\?php.*?:\s*\?>|(?<=</a>)\s*<\?php\s*endif;\s*\?>', '', m.group(0)), content, flags=re.DOTALL)
    else:
        content = re.sub(edit_link_pattern, '', content, flags=re.DOTALL)
        
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

process_file('frontend/recursos_humanos/lista_colaboradores.php', True)
process_file('frontend/recursos_humanos/lista_colaboradores_usr.php', False)
process_file('frontend/recursos_humanos/lista_excolaboradores.php', True)
process_file('frontend/recursos_humanos/lista_excolaboradores_usr.php', False)

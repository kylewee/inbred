import paramiko

host = "gator2117.hostgator.com"
user = "cpunccte"
password = "4299BbCtgw5hcsCh"
port = 2222

db_host = "localhost"
db_name = "cpunccte_mechanic"
db_user = "cpunccte_kylewee"
db_pass = "R0ckS0l!dR0ck"

# Paths on remote server
crm_config_path = "public_html/mechanic/crm/config/database.php"
api_config_path = "public_html/mechanic/api/.env.local.php"

# PHP config templates (simplified for replacement)
# We will read the files, replace lines, and write back.

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(host, port=port, username=user, password=password)

def update_remote_file(client, file_path, replacements):
    print(f"Updating {file_path}...")
    sftp = client.open_sftp()
    try:
        with sftp.open(file_path, 'r') as f:
            content = f.read().decode('utf-8')
        
        new_content = content
        for search, replace in replacements.items():
            # Simple string replacement for now, assuming standard format
            # A more robust regex approach would be safer if formatting varies widely
            import re
            new_content = re.sub(search, replace, new_content)
            
        with sftp.open(file_path, 'w') as f:
            f.write(new_content)
        print(f"Successfully updated {file_path}")
    except Exception as e:
        print(f"Failed to update {file_path}: {e}")
    finally:
        sftp.close()

# Replacements for CRM Config
crm_replacements = {
    r"define\('DB_SERVER', '.*?'\);": f"define('DB_SERVER', '{db_host}');",
    r"define\('DB_SERVER_USERNAME', '.*?'\);": f"define('DB_SERVER_USERNAME', '{db_user}');",
    r"define\('DB_SERVER_PASSWORD', '.*?'\);": f"define('DB_SERVER_PASSWORD', '{db_pass}');",
    r"define\('DB_DATABASE', '.*?'\);": f"define('DB_DATABASE', '{db_name}');"
}

# Replacements for API Config
api_replacements = {
    r"'DB_HOST' => '.*?'": f"'DB_HOST' => '{db_host}'",
    r"'DB_USER' => '.*?'": f"'DB_USER' => '{db_user}'",
    r"'DB_PASS' => '.*?'": f"'DB_PASS' => '{db_pass}'",
    r"'DB_NAME' => '.*?'": f"'DB_NAME' => '{db_name}'",
    # Fallback for define() style if mixed
    r"define\('DB_HOST', '.*?'\);": f"define('DB_HOST', '{db_host}');",
    r"define\('DB_USER', '.*?'\);": f"define('DB_USER', '{db_user}');",
    r"define\('DB_PASS', '.*?'\);": f"define('DB_PASS', '{db_pass}');",
    r"define\('DB_NAME', '.*?'\);": f"define('DB_NAME', '{db_name}');"
}

update_remote_file(client, crm_config_path, crm_replacements)
update_remote_file(client, api_config_path, api_replacements)

client.close()

import paramiko

host = "gator2117.hostgator.com"
user = "cpunccte"
password = "4299BbCtgw5hcsCh"
port = 2222

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(host, port=port, username=user, password=password)

commands = [
    "cd public_html/website_7e9e6396",
    "mkdir -p crm_delete_later",
    "mv crm/* crm_delete_later/ 2>/dev/null || true",  # Move contents if any
    "rmdir crm 2>/dev/null || true",  # Remove empty dir
    "unzip ../tmp/rukovoditel_3.6.3.zip -d .",
    "mv rukovoditel_3.6.3 crm",
    "cd crm/plugins",
    "unzip ../../../tmp/rukovoditel_ext_3.6.3.zip",
    "mv ext/* .",
    "rmdir ext",
    "chmod -R 755 .",
    "cd ..",
    "cat > .htaccess << 'EOF'\n<FilesMatch \\.php$>\n    SetHandler application/x-httpd-ea-php83\n</FilesMatch>\nOptions -Indexes\nDirectoryIndex index.php\n<IfModule mod_rewrite.c>\nRewriteEngine On\nRewriteBase /crm/\nRewriteRule ^index\\.php$ - [L]\nRewriteCond %{REQUEST_FILENAME} !-f\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteRule . /crm/index.php [L]\n</IfModule>\nEOF",
    "ls -la"
]

print("Installing Rukovoditel...")
for cmd in commands:
    print(f"Running: {cmd}")
    stdin, stdout, stderr = client.exec_command(cmd)
    out = stdout.read().decode()
    err = stderr.read().decode()
    if out: print(out)
    if err: print(f"Error: {err}")

client.close()
import paramiko

host = "gator2117.hostgator.com"
user = "cpunccte"
password = "4299BbCtgw5hcsCh"
port = 2222

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(host, port=port, username=user, password=password)

# Read current file
cmd_read = "cat public_html/website_7e9e6396/crm/index.php"
stdin, stdout, stderr = client.exec_command(cmd_read)
content = stdout.read().decode()

# Prepend error reporting
if "ini_set('display_errors', 1);" not in content:
    new_content = "<?php\nini_set('display_errors', 1);\nini_set('display_startup_errors', 1);\nerror_reporting(E_ALL);\n?>" + content.replace("<?php", "", 1)
    
    # Write back
    sftp = client.open_sftp()
    with sftp.open("public_html/website_7e9e6396/crm/index.php", "w") as f:
        f.write(new_content)
    sftp.close()
    print("Enabled error reporting in crm/index.php")
else:
    print("Error reporting already enabled.")

client.close()

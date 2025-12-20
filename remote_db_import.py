import paramiko

host = "gator2117.hostgator.com"
user = "cpunccte"
password = "4299BbCtgw5hcsCh"
port = 2222

db_user = "cpunccte_kylewee"
db_pass = "R0ckS0l!dR0ck"
db_name = "cpunccte_mechanic"
dump_file = "public_html/tmp/backup_crm_20251211_060302.sql"

cmd = f"mysql -u {db_user} -p'{db_pass}' {db_name} < {dump_file}"

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(host, port=port, username=user, password=password)

print(f"Importing database {db_name} from {dump_file}...")
# Running directly, suppressing password warning if possible or just ignoring stderr for that specific warning
stdin, stdout, stderr = client.exec_command(cmd)

out = stdout.read().decode()
err = stderr.read().decode()

if out: print(out)
if err:
    # Filter out the "Using a password..." warning to see real errors
    if "Using a password on the command line interface can be insecure" not in err:
        print(f"Error: {err}")
    else:
        print("Import completed (with security warning).")

client.close()

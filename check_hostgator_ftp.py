from ftplib import FTP_TLS, FTP

host = "gator2117.hostgator.com"
user = "cpunccte"
password = "4299BbCtgw5hcsCh"

try:
    print(f"Connecting to FTP {host}...")
    # Try explicit TLS first as it's more secure and often required
    try:
        ftps = FTP_TLS(host)
        ftps.login(user, password)
        ftps.prot_p() # switch to secure data connection
        print("Connected via FTP_TLS successfully.")
        ftps.cwd("public_html/tmp")
        print("Changed directory to public_html/tmp")
        files = ftps.nlst()
        print("Files found:", files)
        ftps.quit()
    except Exception as e_tls:
        print(f"FTP_TLS failed: {e_tls}")
        print("Retrying with standard FTP...")
        ftp = FTP(host)
        ftp.login(user, password)
        print("Connected via standard FTP successfully.")
        ftp.cwd("public_html/tmp")
        print("Changed directory to public_html/tmp")
        files = ftp.nlst()
        print("Files found:", files)
        ftp.quit()
        
except Exception as e:
    print(f"FTP Connection failed: {e}")

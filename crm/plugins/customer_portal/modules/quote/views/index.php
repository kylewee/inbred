<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Your Quote - Mechanics Saint Augustine</title>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .portal-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 48px;
            max-width: 500px;
            width: 100%;
            margin: 20px;
        }
        .portal-header {
            text-align: center;
            margin-bottom: 32px;
        }
        .portal-header h1 {
            color: #2d3748;
            margin: 0 0 8px;
            font-size: 28px;
            font-weight: 700;
        }
        .portal-header p {
            color: #718096;
            margin: 0;
            font-size: 16px;
        }
        .form-group {
            margin-bottom: 24px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2d3748;
            font-weight: 600;
            font-size: 14px;
        }
        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.2s;
            box-sizing: border-box;
        }
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn-primary {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        .btn-primary:active {
            transform: translateY(0);
        }
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
        }
        .alert-danger {
            background: #fff5f5;
            border: 1px solid #fc8181;
            color: #c53030;
        }
        .help-text {
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #718096;
            font-size: 14px;
        }
        .help-text a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .help-text a:hover {
            text-decoration: underline;
        }
        .icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            color: white;
            font-size: 32px;
        }
    </style>
</head>
<body>
    <div class="portal-container">
        <div class="icon">
            <i class="fa fa-wrench"></i>
        </div>

        <div class="portal-header">
            <h1>Check Your Quote</h1>
            <p>Enter your phone number to view your service estimate</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fa fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="phone">
                    <i class="fa fa-phone"></i> Phone Number
                </label>
                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    class="form-control"
                    placeholder="(904) 555-1234"
                    value="<?= htmlspecialchars($phone_searched) ?>"
                    required
                    autofocus
                >
                <small style="color: #718096; display: block; margin-top: 8px;">
                    Enter the phone number you called us from
                </small>
            </div>

            <button type="submit" class="btn-primary">
                <i class="fa fa-search"></i> Find My Quote
            </button>
        </form>

        <div class="help-text">
            <p>
                Need help?<br>
                Call us at <a href="tel:+19047066669">(904) 706-6669</a>
            </p>
        </div>
    </div>

    <script>
        // Format phone number as user types
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                if (value.length <= 3) {
                    e.target.value = '(' + value;
                } else if (value.length <= 6) {
                    e.target.value = '(' + value.slice(0, 3) + ') ' + value.slice(3);
                } else {
                    e.target.value = '(' + value.slice(0, 3) + ') ' + value.slice(3, 6) + '-' + value.slice(6, 10);
                }
            }
        });
    </script>
</body>
</html>

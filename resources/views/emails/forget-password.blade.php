<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="margin: 0; padding: 0; background-color: #6C7A89;">
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #6C7A89;">
    <tr>
        <td style="text-align: center;padding: 50px;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width: 600px; margin: 0 auto;">
                <tr>
                    <td style="background-color: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 1px 4px rgba(0, 0, 0, 0.16);">
                        <h2 style="margin-bottom: 20px;">Redefinição de Senha</h2>
                        <p>
                            Você está recebendo este e-mail porque recebemos uma solicitação para redefinir a senha da sua conta.
                        </p>
                        <p>
                            Clique no botão abaixo para redefinir sua senha:
                        </p>
                        <p style="text-align: center; margin: 30px 0;">
                            <a href="{{ $resetLink }}" style="background-color: #007BFF; color: #fff; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">Redefinir Senha</a>
                        </p>
                        <p>
                            Ou copie e cole o link abaixo no seu navegador:
                        </p>
                        <p style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; word-break: break-all; font-family: monospace; font-size: 14px; border: 1px solid #e9ecef;">
                            {{ $resetLink }}
                        </p>
                        <p style="color: #6c757d; font-size: 14px; margin-top: 30px;">
                            Se você não solicitou uma redefinição de senha, ignore este e-mail. Este link expira em 60 minutos por segurança.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>

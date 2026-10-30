<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
        .container { max-width: 520px; margin: 40px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .header { background: #00c1b3; padding: 32px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; font-weight: 700; }
        .body { padding: 32px; }
        .body p { color: #374151; font-size: 15px; line-height: 1.6; margin: 0 0 16px; }
        .btn { display: inline-block; margin: 8px 0 24px; background: #00c1b3; color: #fff !important; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-size: 15px; font-weight: 600; }
        .footer { padding: 16px 32px; background: #f9fafb; border-top: 1px solid #e5e7eb; }
        .footer p { font-size: 12px; color: #9ca3af; margin: 0; }
        .link-fallback { font-size: 12px; color: #6b7280; word-break: break-all; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>{{ $nomeClinica }}</h1>
    </div>
    <div class="body">
        <p>Olá, <strong>{{ $usuario->name }}</strong>!</p>
        <p>Você foi convidado para acessar o sistema de gestão da clínica <strong>{{ $nomeClinica }}</strong>.</p>
        <p>Clique no botão abaixo para ativar sua conta e criar sua senha:</p>
        <a href="{{ $linkConvite }}" class="btn">Ativar minha conta</a>
        <p>Este link expira em <strong>72 horas</strong>. Se não foi você, ignore este e-mail.</p>
    </div>
    <div class="footer">
        <p>Se o botão não funcionar, copie e cole este link no navegador:</p>
        <p class="link-fallback">{{ $linkConvite }}</p>
    </div>
</div>
</body>
</html>

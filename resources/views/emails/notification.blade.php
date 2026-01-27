@php
  $appName = $appName ?? config('app.name', 'CodeX');
  $actionUrl = $actionUrl ?? url('/');
  $actionText = $actionText ?? 'Open App';
  $preheader = $preheader ?? $title ?? $appName;
  $body = $body ?? '';
@endphp
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $title ?? $appName }}</title>
    <style>
      body { margin: 0; padding: 0; background: #f6f7fb; font-family: Arial, Helvetica, sans-serif; color: #111827; }
      .preheader { display: none !important; visibility: hidden; opacity: 0; color: transparent; height: 0; width: 0; }
      .container { width: 100%; padding: 24px 0; }
      .card { width: 92%; max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 14px; border: 1px solid #e5e7eb; box-shadow: 0 8px 24px rgba(17,24,39,0.06); }
      .header { padding: 18px 24px; border-bottom: 1px solid #e5e7eb; background: linear-gradient(180deg, #ffffff 0%, #fafafa 100%); }
      .brand { font-size: 18px; font-weight: 700; letter-spacing: 0.2px; }
      .content { padding: 22px 24px; }
      .title { font-size: 20px; font-weight: 700; margin: 0 0 10px 0; }
      .message { font-size: 14px; line-height: 1.6; color: #374151; margin: 0 0 18px 0; }
      .button { display: inline-block; background: #111827; color: #ffffff !important; text-decoration: none; padding: 10px 16px; border-radius: 8px; font-size: 14px; font-weight: 600; }
      .footer { padding: 16px 24px 20px; border-top: 1px solid #e5e7eb; font-size: 12px; color: #6b7280; }
      .muted { color: #6b7280; }
      @media (max-width: 600px) {
        .header, .content, .footer { padding-left: 16px; padding-right: 16px; }
        .title { font-size: 18px; }
      }
    </style>
  </head>
  <body>
    <div class="preheader">{{ $preheader }}</div>
    <div class="container">
      <div class="card">
        <div class="header">
          <div class="brand">{{ $appName }}</div>
        </div>
        <div class="content">
          <h1 class="title">{{ $title ?? $appName }}</h1>
          <p class="message">{!! nl2br(e($body)) !!}</p>
          <a class="button" href="{{ $actionUrl }}">{{ $actionText }}</a>
        </div>
        <div class="footer">
          <div class="muted">You are receiving this email because of your notification settings.</div>
          <div class="muted">© {{ date('Y') }} {{ $appName }}.</div>
        </div>
      </div>
    </div>
  </body>
</html>

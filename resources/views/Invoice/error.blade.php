<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الفاتورة غير متاحة</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --bg-top: #d6e6f7;
            --bg-bottom: #eef4fb;
            --surface: #ffffff;
            --border: #e2eaf4;
            --text: #1c2a3a;
            --muted: #8494a8;
            --badge-bg: #e8f2fb;
            --badge-brd: #b8d4ed;
            --badge-text: #3a7fc1;
            --danger-top: #f87777;
            --danger-bot: #ef4444;
        }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            background: linear-gradient(170deg, var(--bg-top) 0%, var(--bg-bottom) 55%, #ffffff 100%);
            font-family: 'Tajawal', sans-serif;
            color: var(--text);
        }

        /* ── logo (centered) ── */
        header {
            width: 100%;
            padding: 4.5rem 1rem 1.5rem;
            display: flex;
            justify-content: center;
        }

        .logo img {
            height: 60px;
            width: auto;
        }

        /* ── page center ── */
        .page-center {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem 1.5rem 3rem;
            width: 100%;
            position: relative;
        }

        /* ghost receipt behind the card */
        .page-center::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -54%);
            width: 380px;
            height: 460px;
            background: rgba(255, 255, 255, .40);
            border-radius: 20px;
            pointer-events: none;
        }

        /* ── card ── */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            max-width: 520px;
            width: 100%;
            padding: 3rem 2.5rem 2.5rem;
            text-align: center;
            position: relative;
            z-index: 1;
            box-shadow:
                0 1px 3px rgba(90, 120, 160, .06),
                0 12px 40px rgba(90, 120, 160, .10);
            animation: rise .5s cubic-bezier(.22, 1, .36, 1) both;
        }

        @keyframes rise {
            from {
                opacity: 0;
                transform: translateY(18px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ── warning icon ── */
        .icon-wrap {
            width: 90px;
            height: 90px;
            margin: 0 auto 2rem;
            border-radius: 50%;
            background: linear-gradient(160deg, var(--danger-top) 0%, var(--danger-bot) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(239, 68, 68, .28);
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                box-shadow: 0 10px 30px rgba(239, 68, 68, .28);
            }

            50% {
                box-shadow: 0 10px 44px rgba(239, 68, 68, .46);
            }
        }

        .icon-wrap svg {
            width: 40px;
            height: 40px;
            color: #ffffff;
        }

        /* ── headings ── */
        .title-ar {
            font-size: 1.38rem;
            font-weight: 700;
            color: var(--text);
            line-height: 1.65;
            margin-bottom: .5rem;
        }

        .title-en {
            font-size: .88rem;
            font-weight: 400;
            color: var(--muted);
            direction: ltr;
            margin-bottom: 2rem;
        }

        /* ── divider ── */
        .divider {
            border: none;
            border-top: 1px solid var(--border);
            margin: 0 0 1.75rem;
        }

        /* ── book-id badge — BLUE ── */
        .book-id {
            display: block;
            background: var(--badge-bg);
            border: 1px solid var(--badge-brd);
            color: var(--badge-text);
            font-size: .88rem;
            font-weight: 600;
            padding: .65rem 1.5rem;
            border-radius: 50px;
            letter-spacing: .07em;
            direction: ltr;
            margin-bottom: 1.75rem;
            width: 100%;
        }

        /* ── footer note ── */
        .footer-note {
            font-size: .83rem;
            color: var(--muted);
        }
    </style>
</head>

<body>

    <header>
        <a  class="logo">
            <img src="{{ asset('images/naqi-logo.png') }}" alt="NAQI نقي">
        </a>
    </header>

    <div class="page-center">
        <div class="card">

            <div class="icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                </svg>
            </div>

            <p class="title-ar">الفاتورة الخاصة بك لم يتم إصدارها،<br>يرجى المحاولة فيما بعد</p>
            <p class="title-en">Your invoice has not been issued. please try again later</p>

            <hr class="divider">

            @if(!empty($bookId))
                <div class="book-id">{{ $bookId }}</div>
            @endif

            <p class="footer-note">اذا استمرت المشكلة تواصل مع الدعم الفني</p>

        </div>
    </div>

</body>

</html>

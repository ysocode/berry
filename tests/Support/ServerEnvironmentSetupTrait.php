<?php

declare(strict_types=1);

namespace Tests\Support;

trait ServerEnvironmentSetupTrait
{
    /**
     * @var array<string, mixed>
     */
    private array $originalServer = [];

    /**
     * @var array<string, string>
     */
    private array $originalCookie = [];

    /**
     * @var array<string, string|array<int|string, mixed>>
     */
    private array $originalGet = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupSuperglobals();
        $this->initializeFakeEnvironment();
    }

    protected function tearDown(): void
    {
        $this->restoreSuperglobals();

        parent::tearDown();
    }

    private function backupSuperglobals(): void
    {
        /**
         * @var array<string, mixed> $_SERVER
         * @var array<string, string> $_COOKIE
         * @var array<string, string|array<int|string, mixed>> $_GET
         */
        $this->originalServer = $_SERVER;
        $this->originalCookie = $_COOKIE;
        $this->originalGet = $_GET;
    }

    private function restoreSuperglobals(): void
    {
        $_SERVER = $this->originalServer;
        $_COOKIE = $this->originalCookie;
        $_GET = $this->originalGet;
    }

    private function initializeFakeEnvironment(): void
    {
        $headers = [
            'HTTP_HOST' => 'ysocode.com',
            'HTTP_CONNECTION' => 'keep-alive',
            'HTTP_PRAGMA' => 'no-cache',
            'HTTP_CACHE_CONTROL' => 'no-cache',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'HTTP_SEC_FETCH_SITE' => 'none',
            'HTTP_SEC_FETCH_MODE' => 'navigate',
            'HTTP_SEC_FETCH_DEST' => 'document',
            'HTTP_ACCEPT_ENCODING' => 'gzip, deflate, br, zstd',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.9,pt-BR;q=0.8,pt;q=0.7',
            'HTTP_COOKIE' => '_gcl_au=1.1.845465435.1753901012; _ga=GA1.1.700403314.1753901012; _fbp=fb.0.1753901012187.243633908236698624; __utma=111872281.700403314.1753901012.1754418224.1754418224.1; __utmz=111872281.1754418224.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none); cookie_notice=1; SL_C_23361dd035530_SID={"8e6f7a9cda789c26c9d13b3d32bb50aed387df72":{"sessionId":"VYnct6u3hhavolOwTq43N","visitorId":"aAnzzELH7fUwkke4m0Voj"}}; _ga_GBVEKN2FFG=GS2.1.s1754575621$o1$g1$t1754577353$j60$l0$h0; _ga_BCNNFTTB57=GS2.1.s1754938232$o1$g1$t1754938837$j60$l0$h0; _ga_WFERXYKPPF=GS2.1.s1755193176$o5$g1$t1755194514$j60$l0$h0; _ga_48TNLV2CVZ=GS2.1.s1755193177$o5$g1$t1755194514$j60$l0$h0; AdoptVisitorId=KYYwRgrALCAmBMBaAHATgIwAZFXiEKE6UiYwA7BBPJiAMyoBs5QA; _ga_F75NKY8K46=GS2.1.s1755195347$o1$g1$t1755196281$j60$l0$h0; PHPSESSID=sts3bql2ktv2qpak35mijh7rtm; _ga_JZ9W8T667F=GS2.1.s1755563079$o5$g0$t1755563079$j60$l0$h178854805',
        ];

        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_SCHEME' => 'https',
            'SERVER_NAME' => 'ysocode.com',
            'SERVER_PORT' => 443,
            'REQUEST_URI' => '/?query=param',
            'QUERY_STRING' => 'query=param',
            ...$headers,
        ];

        $cookiePairs = explode(';', $headers['HTTP_COOKIE']);
        foreach ($cookiePairs as $pair) {
            [$name, $value] = array_map(trim(...), explode('=', $pair, 2));
            $_COOKIE[$name] = $value;
        }

        parse_str($_SERVER['QUERY_STRING'], $_GET);
    }
}

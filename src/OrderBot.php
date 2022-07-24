<?php

require_once './vendor/autoload.php';

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;

const EMAIL = 'my_address@example.com';
const PASS = 'pass123';

function setUp()
{
    // selenium
    $host = 'http://localhost:4444/wd/hub';
    // chrome ドライバーの起動
    $driver = RemoteWebDriver::create($host, DesiredCapabilities::chrome());

    return $driver;
}

function cartIn($driver, $url, $quantity)
{
    // 指定URLへ遷移
    $driver->get($url);

    // 数量設定
    setQuantity($driver, $quantity);
    $form = $driver->findElement(WebDriverBy::xpath('//form[@action="https://hoge.shop.co.jp/CGI/hoge/shop/shopping.cgi/in"]'));
    $form->submit();

    $handles = $driver->getWindowHandles();
    if (count($handles) >= 2) {
        $driver->switchTo()->window($handles[1]);
        // ログイン
        if (login($driver)) {
            memberConfirm($driver);

            return true;
        } elseif ($driver->getCurrentURL() == 'https://hoge.shop.co.jp/CGI/hoge/shop/shopping.cgi/in') {
            return true;
        }

        return false;
    }
}

function setQuantity($driver, $quantity = 1)
{
    $select_el = $driver->findElement(WebDriverBy::className('radioOn'))->findElement(WebDriverBy::tagName('select'));
    $select = new WebDriverSelect($select_el);

    return $select->selectByValue($quantity);
}

function login($driver)
{
    if ($driver->getCurrentURL() == 'https://hoge.shop.co.jp/member/hoge/login/index.html') {
        $email = $driver->findElement(WebDriverBy::xpath('//input[@name="email"]'));
        $email->sendKeys(EMAIL);
        $password = $driver->findElement(WebDriverBy::xpath('//input[@name="passwd"]'));
        $password->sendKeys(PASS);
        $login = $driver->findElement(WebDriverBy::xpath('//form[@action="/CGI/hoge/shop/shopping.cgi/mem_conf"]'));
        $login->submit();

        return true;
    }

    return false;
}

function memberConfirm($driver)
{
    if ($driver->getCurrentURL() == 'https://hoge.shop.co.jp/CGI/hoge/shop/shopping.cgi/mem_conf') {
        $confirm = $driver->findElement(WebDriverBy::xpath('//input[@name="email"]'))->getAttribute('value');
        if ($confirm == EMAIL) {
            $driver->findElement(WebDriverBy::xpath('//form[@action="https://hoge.shop.co.jp/CGI/hoge/shop/shopping.cgi/mem_fin"]'))->submit();

            return true;
        }

        return false;
    }

    return false;
}

function buy($driver)
{
    if ($driver->findElements(WebDriverBy::xpath('//input[@alt="注文を確認する"]')) > 0) {
        $handles = $driver->getWindowHandles();
        if ($handles >= 2) {
            $driver->switchTo()->window($handles[0]);
            $driver->get('https://hoge.shop.co.jp/CGI/hoge/shop/shopping.cgi/cart_conf');
            if ($driver->findElements(WebDriverBy::xpath('//input[@alt="お届け先を確認する"]')) > 0) {
                $driver->get('https://hoge.shop.co.jp/CGI/hoge/shop/shopping.cgi/send_conf');
                if ($driver->findElements(WebDriverBy::xpath('//input[@value="支払方法の選択"]')) > 0) {
                    $driver->get('https://hoge.shop.co.jp/CGI/hoge/shop/shopping.cgi/order_in');
                    $payment = $driver->findElements(WebDriverBy::xpath('//label[@for="radio01"]'));
                    if ($payment > 0) {
                        $payment[0]->click();
                        $confirm = $driver->findElements(WebDriverBy::xpath('//input[@value="購入内容を確認する"]'));
                        if ($confirm > 0) {
                            $confirm[0]->click();
                            $pay_con = $driver->findElements(WebDriverBy::xpath('//input[@value="お支払情報の入力へ進む "]'));
                            if ($pay_con > 0) {
                                $pay_con[0]->click();
                            }
                        }
                    }
                }
            };
        }
    }
}

$items = [
    // 商品1
    '10' => 'https://hoge.shop.co.jp/CGI/hoge/shop/s_seq.cgi?key=before&code=4530430419050&route=0',
    // 商品2
    '3' => 'https://hoge.shop.co.jp/CGI/hoge/shop/s_seq.cgi?key=before&code=4530430410897&route=0',
];

$driver = setUp();
$finish = false;
foreach ($items as $quantity => $url) {
    // 実行
    $finish = cartIn($driver, $url, $quantity);
}

if ($finish) {
    // 購入処理
    buy($driver);
}

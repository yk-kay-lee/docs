# Cross Origin Resource Sharing - CORS
## 概要
CORSは普通Access-Control-Allow-Origin以外は
あまり気にしなかったというか
あまり知らなかったというのが本当で
個人的にメモしてた内容をまとめてみました。

下記からはCORSの定義です。

HTTPの要請は基本的にCross-Site HTTP Requestsが可能です。
つまり、`<img>`、`<link>`、`<script>`タグで他のドメインのリソースが使用出来ます。

しかし、`<script></script>`に囲まれているスクリプトで生成された
Cross-Site HTTP Requestsは　[Same Origin Policy](https://developer.mozilla.org/ja/docs/Web/Security/Same-origin_policy)　が適用され、
Cross-Site HTTP Requestsが出来ません。

AJAXが広く使われており、`<script></script>`に囲まれているスクリプトで生成される`XMLHttpRequest`についてもCross-Site HTTP Requestsが可能でなければならないという要求が増えて、W3CからCORSという名前の勧告案が出るようになりました。

## CORSの要請の種類

CORSの要請はSimple/Preflight、Credential/Non-Credentialの組み合わせで4つあります。

ブラウザが要請内容を分析して4つの方式のうち該当する方式でサーバーに要請を飛ばすので
プログラマーが目的に合わせて適切な方式を決めてコーディングする。

### Simple Request

下記の3つの条件をすべて満足すれば、Simple Request

* GET、HEAD、POST中の一つの方式を使用する。
* POST方式の場合には、Content-typeが下記三つの一つ。
    * application/x-www-form-urlencoded
    * multipart/form-data
    * text/plain
* カスタムヘッダの伝送はダメ。

Simple Requestは、サーバーに一回要請して、サーバーも一回回答すると処理が終了される。

```Simple-Request
GET /resources/public-data/ HTTP/1.1
Host: bar.other
User-Agent: Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.5; en-US; rv:1.9.1b3pre) Gecko/20081130 Minefield/3.1b3pre
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8
Accept-Language: en-us,en;q=0.5
Accept-Encoding: gzip,deflate
Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7
Connection: keep-alive
Referer: http://foo.example/examples/access-control/simpleXSInvocation.html
Origin: http://foo.example


HTTP/1.1 200 OK
Date: Mon, 01 Dec 2008 00:23:53 GMT
Server: Apache/2.0.61
Access-Control-Allow-Origin: *
Keep-Alive: timeout=2, max=100
Connection: Keep-Alive
Transfer-Encoding: chunked
Content-Type: application/xml

[Data]
```

### Preflight Request

Simple Request条件に該当しなければブラウザはPreflight Request方式で要請する。

したがって、Preflight Requestは

* GET、HEAD、POST以外の他の方式からも要請を送ることができるし、
* application/xmlのように他のContent-typeに要請を送ることもできるし、
* カスタムヘッダも使用できる。

名前からも分かるように、Preflight Requestは予備要請と本要請に分かれて転送される。

先にサーバーに予備の要請(Preflight Request)を送ってサーバは予備の要請について回答して、
その次に本要請(Actual Request)をサーバに送って、サーバーも本要請に対して回答する。

しかし、予備要請と本要請に対するサーバの回答をプログラマーがコード内で区分して処理することではない。
プログラマーが`Access-Control-`系のResponse Headerだけ適切に決めてくれれば、
OPTIONSの要請で来る予備要請とGET、POST、HEAD、PUT、DELETEなどでの本要請の処理は、サーバが自分で処理する。

下はPreflight Requestsで交わされているHEADERの例

再び強調しますが下記内容でプログラマーがOPTIONS要請の処理ロジックとPOST要請の処理ロジックを区分して実装することではない。

```Preflight-Request-and-Actual-Request
OPTIONS /resources/post-here/ HTTP/1.1
Host: bar.other
User-Agent: Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.5; en-US; rv:1.9.1b3pre) Gecko/20081130 Minefield/3.1b3pre
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8
Accept-Language: en-us,en;q=0.5
Accept-Encoding: gzip,deflate
Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7
Connection: keep-alive
Origin: http://foo.example
Access-Control-Request-Method: POST
Access-Control-Request-Headers: X-PINGOTHER


HTTP/1.1 200 OK
Date: Mon, 01 Dec 2008 01:15:39 GMT
Server: Apache/2.0.61 (Unix)
Access-Control-Allow-Origin: http://foo.example
Access-Control-Allow-Methods: POST, GET, OPTIONS
Access-Control-Allow-Headers: X-PINGOTHER
Access-Control-Max-Age: 1728000
Vary: Accept-Encoding
Content-Encoding: gzip
Content-Length: 0
Keep-Alive: timeout=2, max=100
Connection: Keep-Alive
Content-Type: text/plain

POST /resources/post-here/ HTTP/1.1
Host: bar.other
User-Agent: Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.5; en-US; rv:1.9.1b3pre) Gecko/20081130 Minefield/3.1b3pre
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8
Accept-Language: en-us,en;q=0.5
Accept-Encoding: gzip,deflate
Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7
Connection: keep-alive
X-PINGOTHER: pingpong
Content-Type: text/xml; charset=UTF-8
Referer: http://foo.example/examples/preflightInvocation.html
Content-Length: 55
Origin: http://foo.example
Pragma: no-cache
Cache-Control: no-cache

<?xml version="1.0"?><person><name>Arun</name></person>


HTTP/1.1 200 OK
Date: Mon, 01 Dec 2008 01:15:40 GMT
Server: Apache/2.0.61 (Unix)
Access-Control-Allow-Origin: http://foo.example
Vary: Accept-Encoding
Content-Encoding: gzip
Content-Length: 235
Keep-Alive: timeout=2, max=99
Connection: Keep-Alive
Content-Type: text/plain

[Some GZIP'd payload]
```

### Request with Credential

HTTP CookieとHTTP Authentication情報を認識することができるようにする要請

```Simple-Credential-Request
var invocation = new XMLHttpRequest();
var url = 'http://bar.other/resources/credentialed-content/';

function callOtherDomain(){

  if(invocation) {
    invocation.open('GET', url, true);
    invocation.withCredentials = true;
    invocation.onreadystatechange = handler;
    invocation.send();
  }
  ...
```

要請時`xhr.withCredentials=true`
を指定してCredentialの要請を送ることができるし
サーバはResponse Headerに必ず`Access-Control-Allow-Credentials:true`を含めなければならず、
`Access-Control-Allow-Origin`ヘッダの値には`*`があるとダメで
`http://foo.origin`のような具体的なドメインがないといけない。

```Server-Response-Header-to-Simple-Request-with-Credential
HTTP/1.1 200 OK
Date: Mon, 01 Dec 2008 01:34:52 GMT
Server: Apache/2.0.61 (Unix) PHP/4.4.7 mod_ssl/2.0.61 OpenSSL/0.9.7e mod_fastcgi/2.4.2 DAV/2 SVN/1.4.2
X-Powered-By: PHP/5.2.6
Access-Control-Allow-Origin: http://foo.example
Access-Control-Allow-Credentials: true
Cache-Control: no-cache
Pragma: no-cache
Set-Cookie: pageAccess=3; expires=Wed, 31-Dec-2008 01:34:53 GMT
Vary: Accept-Encoding
Content-Encoding: gzip
Content-Length: 106
Keep-Alive: timeout=2, max=100
Connection: Keep-Alive
Content-Type: text/plain


[text/plain payload]
```

### Request without Credential

CORSの要請は基本的にNon-Credentialの要請なので、`xhr.withCredentials=true`を指定しなければ、Non-Credentialの要請

### CORS関連HTTP Response Headers

サーバーからCORSの要請を処理するさい、指定するヘッダ

### Access-Control-Allow-Origin

Access-Control-Allow-Originヘッダの値に指定されたドメインからの要請だけサーバのリソースにアクセスできるようにする。

```Response-Header
Access-Control-Allow-Origin: <origin> | *
```
`<origin>`には要請ドメインのURIを指定する。
すべてのドメインからのサーバーリソースへのアクセスを許可するには`*`を指定する。
Request with Credentialの場合には*はダメ。

### Access-Control-Expose-Headers

基本的にブラウザに露出されないですがブラウザ側からアクセスできるように許容するヘッダを指定する。

基本的にブラウザに露出されるHTTP Response Headerは以下の6つしかない。

* Cache-Control
* Content-Language
* Content-Type
* Expires
* Last-Modified
* Pragma

次のように`Access-Control-Expose-Headers`をResponse Headerに指定して回答すると、
ブラウザ側でカスタムヘッダ含めて基本的にはアクセスできなかったContent-Lengthヘッダ情報も分かるようになる。

```Response-Header
Access-Control-Expose-Headers: Content-Length, X-My-Custom-Header, X-Another-Custom-Header
```

### Access-Control-Max-Age

Preflight Requestの結果がキャッシュにどれだけ長い間残っているかを示す。

```Response-Header
Access-Control-Max-Age: <delta-seconds>
```

### Access-Control-Allow-Credentials

Request with Credential方式が使用されるかを指定する。

```Response-Header
Access-Control-Allow-Credentials: true | false
```

予備の要請に対する回答に`Access-Control-Allow-Credentials:false`を含めると
本要請はRequest with Credential送信は出来ない。

Simple Requestに`withCredentials=true`が指定されているのにResponse Headerに`Access-Control-Allow-Credentials:true`が明示されていないと
そのResponseはブラウザから無視される。

### Access-Control-Allow-Methods

予備の要請に対するResponse Headerに使用され、
サーバのリソースにアクセスできるHTTP Method方式を指定する。

```Response-Header
Access-Control-Allow-Methods: <method>[, <method>]*
```

### Access-Control-Allow-Headers

予備の要請に対するResponse Headerに使用され、
本要請で使用できるHTTP Headerを指定する。

```Response-Header
Access-Control-Allow-Headers: <field-name>[, <field-name>]*
```

### CORS関連HTTP Request Headers

クライアントがサーバにCORS要請を送信する際使用するヘッダでブラウザが自動的に指定するのでXMLHttpRequestを使用するプログラマーが直接指定する必要はない。

### Origin

Cross-siteの要請を飛ばす要請先のドメインURI。
access controlが適用されるすべての要請に`Origin`ヘッダは必ず含まれる。

```Response-Header
Origin: <origin>
```

`<origin>`はサーバー名(ポートを含む)だけが含まれ、経路情報は含まれない。

`<origin>`は空白の可能性もあるが、ソースがdata URLの場合に有用である。

### Access-Control-Request-Method

予備要請を送る時に含まれて、本要請でどのようなHTTP Methodを使用するかサーバに知らせる。

```Request-Header
Access-Control-Request-Method:<method>
```

### Access-Control-Request-Headers

予備要請を送る時に含まれて、本要請でどのようなHTTP Headerを使用するかサーバに知らせる。

```Request-Header
Access-Control-Request-Headers:<field-name>[,<field-name>]*
```

### XDomainRequest

`XDomainRequest`(XDR)はW3C標準ではなく、IE 8、9で非同期CORS通信のためMicrosoftで作ったオブジェクトです。

* XDRは`setRequestHeader`がない。
* XDRとXHRを区分するためには、`obj.contentType`を使用する。(XHRにはこれがない)
* XDRはhttpとhttpsプロトコルだけが可能


## 結論

* CORSを使えば、AJAXでもSame Origin Policyの制約を超えて他のドメインのリソースを使用することができる。
* CORSを使用するためには
    * クライアントで`Access-Control-**`などのHTTP Headerをサーバーに送らなければならない
    * サーバーも`Access-Control-**`などのHTTP Headerをクライアントに返信するようになっていなければならない。

## 参照資料
* http://www.w3.org/TR/cors/
* https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS


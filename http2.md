# HTTP/2を導入する前に

## 概要
最近、rooVeRのwww側での速度について色々調べていて
(https://redmine.showcase-gig.com/issues/12220)
もっと根本的な改善はないのかについて工夫した結果
www側のruby on railsの構成は除いても
体感的な改善、特にwwwみたいにcss, js, cookie, imagesの使用が多いサービスでは
HTTP2支援も速度改善の良い案ではないかなと。
ということから
まずは
HTTP/1.1 vs HTTP/2の概念から調べてみました。
基本的には
id側を先にHTTP/2に対応してその手順をwww側に渡す形で行こうと思っております。

## HTTP(HyperText Transfer Protocol)/1.1

HTTPは1996年に初めて1.0バージョンがreleaseされて
1999年、現在私たちが公式的に最も多く使用して支援するバージョンの1.1がreleaseされて
約15年間、発展なしで使用されている。

現在のウェブは多量のマルチメディアリソースを処理しなければならず
ウェブページ一つを構成するために多数の非同期のrequestが発生しており、
これを処理するにはHTTP1.1スペックは遅すぎ、非効率的です。

特に最近のようなモバイル環境ではもっとHTTP1.1のスペックは気持ち悪いですね。

ということで
まずはHTTP/1.1の動作方式について調べてみます。

### HTTP/1.1動作方式

ご存知の通り
HTTPは、ウェブ上でクライアント(IE、Chrome、Firefox)とサーバー(ウェブサーバeg:httpd、nginx、etc…)間の通信を行うためのプロトコルです。

HTTP 1.1プロトコルはクライアントとサーバー間通信のために次のようなプロセスで動作します。

![http1.1process.png (32.1 kB)](https://img.esa.io/uploads/production/attachments/1834/2016/12/06/7844/d5cd96b3-5085-423f-882b-e2e120112792.png)

あまりにも簡単ですので説明することもないですね。
HTTP/1.1は基本的にConnection当たり一つのrequestを処理するように設計されています。
それで上記のイメージのように同時伝送が不可能で、requestとresponseが順次的に行われます。

そのため、HTTP文書のなかに含まれた多数のリソース(Images、CSS、Script)を処理するために
requestするリソース量に対してLatencyは長くなります。

HTTP1.1の短所をもっと調べてみると

### HOL(Head Of Line)Blocking–特定responseの遅延

Web環境でHOLBは実際に二種類が存在します。

* HTTPのHOL Blocking
* TCPのHOL Blocking

主にウェブなのでHTTPのHOLBに対してもっと調べてみると。
(TCPのHOLBは概念的にHTTPのHOLBと似ていますが、詳細は異なるので詳しいことは自分で探してみてください)🙂


HTTP/1.1のconnection当たり一つのrequestの処理を改善できる手法の中では
pipeliningが存在していますが
これは一つのConnectionを通じて多数のファイルをrequest/responseできる技法で
これである程度の性能向上はできますが大きな問題点が一つあります。

一つの接続で3つのイメージ(a.png、b.png、c.png)を得られようとする場合
HTTPのrequest手順は以下のようになります。

```
| --- a.png --- |
            | --- b.png --- |
                        | --- c.png --- |
                        
```

順に最初のイメージをrequestしてresponseを受けて
次のイメージをrequestすることになりますが
もし最初のイメージをrequestしてresponseが遅れると
下記のように2~3番目のイメージは当然最初のイメージのresponseとして処理が完了する前まで
待機することになり、
このような現象をHTTPのHead of Line Blockingと呼び、
パイプライニングの大きな問題点の一つであります。

```
| ------------------------------- a.png --------------- --- |
                                                       | -b.png- |
                                                               | --c.png-- |

```


### RTT( Round Trip Time ) 増加

http/1.1の場合、
一般的に一つのconnectionに一つのrequestを処理する。 
このため、毎回request別にconnectionを作ることになって
TCP上で動作するHTTPの特性上、3-way Handshakeが繰り返して
また不要なRTT増加とネットワーク遅延が発生して性能を低下させることになります。

### 重いHeader構造(特にCookie)

http/1.1のヘッダには多くのメタ情報が保存されていますね。
ユーザーが訪問したウェブページは多数のhttpのrequestが発生することになりますが
この場合、毎回、request時ごとに重複されたヘッダー値を伝送することになり(
別途のdomain shardingをしていなかった場合)
また、当該domainに設定されたcookie情報も毎回、request時ごとにヘッダに含まれて送信され、
ある時はrequestを通じて伝送しようとする値よりヘッダの値がさらに大きい場合も多いです。
(User-Agent情報一つだけでもおよそ120Byteが超える。 (涙))

### こういうことを改善しようとした涙ぐましい努力

このようなhttp/1.1の問題点と非効率性を克服しようと涙ぐましい努力を行ってきており
今この瞬間も努力している。 
代表的な努力を紹介してみます。

### Image Spriting

ゲームでもよくつかわれているspriteですね。
ウェブページを構成する様々なアイコンイメージファイルのrequestの回数を減らすため、
アイコンを一つの大きなイメージで作った後、CSSで該当イメージの座標値を指定して表示する。


![imagespriting.png (38.6 kB)](https://img.esa.io/uploads/production/attachments/1834/2016/12/06/7844/477395ba-0378-4bdb-87ac-70d5275a6209.png)

ソース:Google Search

### Domain Sharding

最近のブラウザはhttp/1.1の短所を克服するため
多数のConnectionを生成して並列にrequestを送ったりもします。
しかし、ブラウザあまりDomainあたりのConnection数の制限が存在して
これもhttp/1.1の根本的な解決策ではないです。

![expressen-sharding.jpg (294.9 kB)](https://img.esa.io/uploads/production/attachments/1834/2016/12/06/7844/56a652ee-0d5a-4352-a530-0039accdfe49.jpg)
ソース:Google Search

### Minify CSS/Javascript

httpを通じて伝送されるデータの容量を減らすため
CSS、Javascriptコードをminifyして適用することもありますね。

![code-minified.jpg (224.4 kB)](https://img.esa.io/uploads/production/attachments/1834/2016/12/06/7844/dc27e906-bfca-4373-9fac-c359ec6fda55.jpg)
ソース:Google Search

### Data URI Scheme

Data URI SchemeはHTML文書内のイメージリソースをBase64でエンコードされたイメージデータに
直接記述する方式でこれを通じてrequest数を減らすこともあります。

<img width="365.25" alt="base64img.png (72.7 kB)" src="https://img.esa.io/uploads/production/attachments/1834/2016/12/06/7844/4c5a48f3-fe4e-41f2-98aa-65c8b964e76a.png">
ソース:Google Search

### Load Faster

* StylesheetをHTML文書の上位に配置
* ScriptをHTML文書の下に配置

こんな涙ぐましい努力もHTTP/1.1の短所を根本的に解決することはできなくて
GoogleはよりスピーディーなWebを実現するため、
throughputという観点ではなくLatencyの観点からHTTPを高速化したSPDY(スピーディ)と呼ばれる
新たなプロトコルを実装しました。
ただ、SPDYはHTTPを通じて伝送を再定義する形で具現されているものです。
SPDYは実際にHTTP/1.1と比べて相当な性能向上と効率性をみせましてこれはHTTP/2の諸案の参考になりました。

(GoogleはSPDYの支援を今年末までするということで現在、google.com、google.co.jpはspdyで通信しているが、これもそのうちhttp/2に変更される予定だそうです。)

<img width="444.75" alt="spdy.png (34.7 kB)" src="https://img.esa.io/uploads/production/attachments/1834/2016/12/06/7844/721a4612-471a-4236-8cb6-81d3840ee5f0.png">

ソース:Naver D2

## HTTP/2

さあ、
それではHTTP/2について。
HTTP/2は上に説明した通りSPDYを基盤で
http2作業グループが2012年10月から開始した新しいプロトコル実装プロジェクトであります。
http2公式githubページの序文を見ると
http2の目的が何か明確に分かることができます。

```

"HTTP/2 is a replacement for how HTTP is expressed"on the wire."It is not a ground-up rewrite of the protocol;HTTP methods、status codes and semantics are the same、and it should be possible to use the same APIs as HTTP/1.x(possibly with some small additions)to represent the protocol.

The focus of the protocol is on performance;specifically、end-user perceived latency、network and server resource usage.One major goal is to allow the use of a single connection from browsers to a Web site."
```


素晴らしいgoogle translatorで翻訳してみると

```
HTTP/2は、HTTPが「有線で」どのように表現されているかを置き換えるものです。これはプロトコルの基本的な書き換えではありません。 HTTPメソッド、ステータスコード、セマンティクスは同じであり、プロトコルを表すためにHTTP / 1.xと同じAPIを使用することができます（場合によっては若干の追加があります）。

プロトコルの焦点はパフォーマンスです。 具体的には、エンドユーザーが認識した待ち時間、ネットワークおよびサーバーリソースの使用率です。 主要な目標の1つは、ブラウザからWebサイトへの単一の接続を使用できるようにすることです。
```

つまり完全に新しいプロトコルを作ったわけではなく性能の向上にフォーカスを合わせたプロトコルということです。

HTTP/2がどのような方法で性能を向上させているか主要要素について調べてみます。


### Multiplexed Streams

1 connectionで同時に複数のメッセージのやり取りができる。
responseは順に関係なくstreamで受け取る。 
HTTP/1.1のConnection Keep-Alive、Pipeliningの改善と思えばいいと思います。

![http2_streams.png (32.9 kB)](https://img.esa.io/uploads/production/attachments/1834/2016/12/06/7844/b584f72d-b759-4062-a42a-0b9ba12f3f30.png)
ソース:kinsta.com 

### Stream Prioritization

例えばクライアントがrequestしたHTML文書のなかにCSSファイル1個とImageファイル2つが存在している場合
これをクライアントがそれぞれrequestした後
ImageファイルよりCSSファイルの受信が遅れる場合、ブラウザのレンダリングが遅くなる問題が発生しますが。HTTP/2の場合、リソース間の依存関係(優先順位)を設定してこのような問題を解決しています。

![http2_weight.png (17.8 kB)](https://img.esa.io/uploads/production/attachments/1834/2016/12/06/7844/5b8e00c4-ba6a-4976-8448-ef34f7cdfd4b.png)
ソース:kinsta.com 

### Server Push

サーバーはクライアントのrequestに対してrequestもしていないリソースを勝手に送信することもあります。

何かというと、
クライアント(いわゆるブラウザ)がHTML文書をrequestし、該当HTMLに複数のリソース(CSS、Image…)が含まれている場合。
HTTP/1.1でクライアントはrequestしたHTML文書を受信した後HTML文書を解釈しながら必要なリソースを再要請する一方
HTTP/2ではServer Pushの技法を通じてクライアントがrequestしていない(HTML文書に含まれたリソース)リソースをPushしてくれる方法でクライアントのrequestを最小化して性能向上を引き出す。
これをPUSH_PROMISEと呼び、PUSH_PROMISEを通じて、サーバが伝送したリソースについてはクライアントはrequestしない。

![http2_push.png (26.0 kB)](https://img.esa.io/uploads/production/attachments/1834/2016/12/06/7844/593071f0-8adf-40b2-99dc-89493fedd976.png)
ソース:kinsta.com 

### Header Compression

HTTP/2はHeader情報を圧縮するため、Header TableとHuffman Encoding手法を使用して処理します。
これをHPACK圧縮方式と呼び、別途の明細書(https://http2.github.io/http2-spec/compression.html)で管理しています。

![googles-ilya-grigorik-on-http-20-34-638.jpg (58.7 kB)](https://img.esa.io/uploads/production/attachments/1834/2016/12/06/7844/fd01afed-8c12-4d3f-b5e6-7bf8e8f94421.jpg)
ソース:Google Search


上のイメージのようにクライアントが二つのrequestを送ったと仮定すると
HTTP/1.xの場合二つのrequest Headerに重複の値が存在してもそのまま重複伝送する。
しかし、
HTTP/2ではHeaderに重複の値が存在する場合、
Static/Dynamic Header Table概念を使用して重複しているHeaderを検出し、重複したHeaderはindex値のみ伝送して重複していないHeader情報の値はHuffman Encoding手法でエンコード処理して配信する。

![3IPWXvR.png (142.4 kB)](https://img.esa.io/uploads/production/attachments/1834/2016/12/06/7844/cc2292a6-db9b-4ad3-a609-37990ad003f0.png)
ソース:Google Search

### HTTP/1.1とHTTP/2の性能比較

両プロトコルの客観的な性能比較の指標は
テスト環境とそれぞれテスト時、外部のインターネット品質などの影響で
正確にはわからないと思いますが
一般的にHTTP/2を使用だけでもウェブ応答速度がHTTP/1.1に比べて15~50%が向上されるといいます。

下記のイメージは同一数/容量のpngのイメージを
ウェブサイトにロードさせてHTTP/1.1とHTTP/2の速度を比較した結果です。

このテストの場合効率性の差が90%以上にもなりますね。

![test1.png (155.6 kB)](https://img.esa.io/uploads/production/attachments/1834/2016/12/06/7844/dc190f4a-ce58-4531-9309-da24ef5e4f80.png)
![test2.png (138.4 kB)](https://img.esa.io/uploads/production/attachments/1834/2016/12/06/7844/4eabd052-8704-4fbb-84d9-8ac71c4d43bd.png)


イメージではHTTPSで表示されていますが我々が知っているHTTPSではなく
HTTP/2ということです。

## 最後に

いまだに多数のサイト(サービス)はHTTP/1.1だけを支援しています。
しかし、知らない間に多くのサービスがすでにHTTP/2を適用していて問題なく使っています。

いくらサーバーでHTTP/2を支援したとしても、クライアントでHTTP/2をサポートしないと使用できないしまだPC用ウェブページでHTTP/2の使用は未支援クライアント(いわゆるブラウザ)が多くて限界もあるかなと思いますが
今の時代は、モバイル時代ではないですか。
一般的に使用しているモバイルDeviceでのchromeやsafariブラウザはHTTP/2を支援していますし
(HTTP/2支援ブラウザリスト：http://caniuse.com/#feat=http2)
これはデータ使用量にも良いですし有線に比べて相対的に遅いLTE、3Gのユーザーにもっと良いサービス利用環境を提供することができますね。

ただウェブサーバにHTTP/2支援モジュールを設置したり、HTTP/2を支援するウェブサーバへの変更することだけでですね。
もちろん、HTTP/2の導入に向けて新たなウェブサーバへの変更作業は、
サービス障害につながる可能性もありますので慎重に判断して処理するべきの作業ですが
これは変更するに十分な価値がある作業だと思います。

いつまで非効率的なHTTP/1.1を放置しているべきか。

既に我々が知っているサービスサイトは全部支援している。

ということで、
rooVeRのid側から
http/2をサービス影響なしの方法で適用しようと思います。
そこからwww側もhttp/2に移行する形で。
詳細の内容はまた次回で。


参考：
https://tools.ietf.org/html/rfc7540
http://d2.naver.com/helloworld/140351
https://kinsta.com/learn/what-is-http2/


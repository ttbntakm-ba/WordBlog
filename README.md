全て自己責任で
\# WordBlog



WordBlog は、PHP と PDO で作られたシンプルなブログ／CMS です。  

記事の投稿・編集・削除、ユーザー管理（ロール付き）、テーマ切り替え、コメント、カテゴリ、検索、HTMLモード付きエディタなど、学習と小規模利用向けの機能を備えています。



\## Features



\- 記事の作成・編集・削除（CRUD）

\- ログイン機能（`users` テーブル＋ `password\_hash` / `password\_verify`）

\- ロール管理（admin / editor / viewer / commenter）

\- テーマ CSS の切り替えとブラウザ上からの編集

\- 記事へのコメント機能

\- 記事一覧のページング（1ページ10件）

\- タイトル・本文のキーワード検索

\- カテゴリ機能（1記事1カテゴリ）とカテゴリ別一覧

\- SQLite または MySQL を選択可能なセットアップ画面

\- 記事エディタの 2 モード

&nbsp; - シンプルMODE（テキスト＋簡単なHTML）

&nbsp; - HTML MODE（保存したHTMLをそのまま出力）



\## Requirements



\- PHP 8.x 以降（PDO拡張有効）

\- Webサーバ（Apache + mod\_php など）

\- SQLite3 または MySQL / MariaDB

\- ブラウザから `setup/setup.php` にアクセスできる環境



\## Installation



1\. プロジェクト一式を Web ルート配下に配置します。



&nbsp;  例:  

&nbsp;  `D:\\test\\xampp\\htdocs\\blog`  

&nbsp;  `/var/www/html/wordblog`



2\. プロジェクト直下に次のファイルがあることを確認します。



&nbsp;  - `simple-config.php`

&nbsp;  - `config.php`（最初は無くてもOK。setup で自動生成）

&nbsp;  - `setup/setup.php`



3\. ブラウザでセットアップ画面にアクセスします。



&nbsp;  ```

&nbsp;  http://localhostなど/blog/setup/setup.php

&nbsp;  ```



4\. セットアップ画面で次を入力します。



&nbsp;  - DB種別: SQLite または MySQL

&nbsp;  - MySQL の場合: ホスト名 / データベース名 / ユーザー名 / パスワード

&nbsp;  - 最初の管理ユーザーのユーザー名・パスワード



5\. 送信すると `config.php` が生成され、以下が自動的に作成されます。



&nbsp;  - DBファイル（SQLite の場合）または MySQL テーブル

&nbsp;  - `posts` / `users` / `settings` / `comments` / `categories` テーブル

&nbsp;  - 最初の admin ユーザー



6\. 完了後、自動的に `index.php` へリダイレクトされます。



&nbsp;  ```

&nbsp;  http://localhostなど/blog/

&nbsp;  ```



\## Usage



\### ログインと管理画面



1\. トップページにアクセスします。



&nbsp;  ```

&nbsp;  http://localhostなど/blog/

&nbsp;  ```



2\. 画面上部の「管理画面にログイン」から、セットアップ時のユーザー名・パスワードでログインします。



3\. ログイン後、「管理画面」では次の操作ができます。



&nbsp;  - 記事の新規作成・編集・削除

&nbsp;  - テーマの切り替え・CSS編集（admin のみ）

&nbsp;  - ユーザー管理（追加・編集・削除、ロール変更 / admin のみ）

&nbsp;  - カテゴリ管理（追加・編集・削除 / admin のみ）



\### 記事とエディターモード



\- 記事は `posts` テーブルに保存されます。

\- 記事編集画面（`article\_form.php`）では次を設定できます。

&nbsp; - タイトル

&nbsp; - カテゴリ（プルダウン）

&nbsp; - エディターモード

&nbsp;   - シンプルMODE: テキスト主体。出力時は `htmlspecialchars` して安全に表示。

&nbsp;   - HTML MODE: 本文に書いた HTML をそのまま出力（管理者用）。

\- フロント側:

&nbsp; - `index.php` … 記事一覧（最新順、10件／ページ、本文の先頭10文字の抜粋）

&nbsp; - `post.php?id=...` … 記事の個別ページ＋コメント欄＋カテゴリ表示



\### コメント



\- `post.php` の下部フォームから、任意の名前と本文でコメント投稿できます。

\- コメントは `comments` テーブルに保存され、記事ごとに新しい順で表示されます。



\### 検索とカテゴリ



\- 検索:

&nbsp; - `index.php` 上部の検索ボックスから、タイトル・本文に対するキーワード検索が可能です。

&nbsp; - 検索結果もページング対応で表示されます。

\- カテゴリ:

&nbsp; - `categories` テーブルでカテゴリ名を管理。

&nbsp; - 記事ごとに `category\_id` を1つ紐づけます。

&nbsp; - `category\_list.php?name=カテゴリ名` で、そのカテゴリの記事一覧を表示します。



\## Roles



\- \*\*admin\*\*

&nbsp; - 記事の追加・編集・削除

&nbsp; - テーマ設定・CSS編集

&nbsp; - ユーザー管理

&nbsp; - カテゴリ管理

\- \*\*editor\*\*

&nbsp; - 記事の追加・編集

\- \*\*viewer / commenter\*\*

&nbsp; - 管理画面の参照のみ（編集不可）

&nbsp; - commenter ロールは将来のコメント権限用として想定



\## Main Files



\- `index.php` … トップページ（記事一覧／検索／ページング）

\- `post.php` … 記事個別ページ＋コメント＋HTMLモード対応

\- `admin.php` … 管理画面トップ

\- `login.php` / `logout.php` … ログイン／ログアウト

\- `article\_form.php` … 記事作成・編集（カテゴリ・エディターモード付き）

\- `delete.php` … 記事削除

\- `user\_list.php` / `user\_form.php` / `user\_delete.php` … ユーザー管理

\- `category\_list.php` / `category\_form.php` / `category\_delete.php` … カテゴリ管理

\- `theme\_edit.php` … テーマCSS編集

\- `config.php` … DB設定＆共通関数（setup から自動生成）

\- `simple-config.php` … config テンプレート

\- `setup/setup.php` … セットアップスクリプト




# IO_ICC

ICC プロファイルを解釈・編集する PHP ライブラリ (IO_Bit に依存)。本体は [IO/ICC.php](IO/ICC.php)。

# 互換性

IO_ISOBMFF と IO_HEIF が composer.json で `yoya/io_icc: ^1.1.3` を要求している。
IO_ISOBMFF (IO/ISOBMFF.php の colr ボックスの処理) は `parse()`、`_tags`、タグの `signature`、`parseTagContent()`、desc/mluc の `tag->ascii` と `tag->records` を直接触っている。

- 既存の挙動を変える修正は、依存側 (`../IO_ISOBMFF`, `../IO_HEIF`) での使われ方を grep で確かめてから行う。
- 明らかなバグ修正以外で挙動が変わるもの (`_header` のキーや値の意味、build の出力、入力検査など) は、メジャーバージョンを上げて扱う。
- メジャーバージョンを上げたら、IO_ISOBMFF と IO_HEIF の composer.json の制約 (`^1.1.3`) も直す必要がある。IO_ICC を先にリリースし、Packagist に出てから依存側を直す。

# テスト

`php -d include_path=.:../IO_Bit tests/run.php` で回帰テストを実行する (IO_Bit は vendor/ か include_path から読む。失敗すると終了コード 1)。バグを直したら、ここにテストを足す。

`test/alltest.php` は `test/*.icc` を parse → rebuild → parse して違いを表示する目視用のもので、失敗しても終了コードは 0。JapanColor2011Coated.icc の desc の `_contentLength` の違いはいつも出る (データは失われていない)。

# リリース作業

バージョン番号は指示されたものを使う。進め方は過去のリリースの形に倣い、迷う点や手順に無い気づきがあれば報告する。

1. 変更の把握
   - 手元が公開済みの状態と揃っていて、リリースに含めない変更が混じっていないことを確かめる。
   - 前回のタグから何が変わったかを、コミットの本文まで読んで把握する。
2. 動作の確認
   - `tests/run.php` が通ることを確かめる。挙動を変えた箇所があれば、IO_ISOBMFF (isobmffprops.php など ICC を読む経路) で壊れないことも確かめる。
3. README の更新
   - README.md と README.en.md が、今回の変更を含めた現状を表しているか見直し、必要なら直す。両者の内容は揃える。
   - 直す必要が無ければ、そう判断したことを報告する。
   - 直したものは、バージョン番号の変更とは別のコミットにする。
4. バージョン番号の更新
   - [IO/ICC.php](IO/ICC.php) 冒頭の `IO_ICC class -- vX.Y.Z` を新しい番号にし、それだけのコミットにする。
   - 過去のメッセージは `update to v1.1.2`、`v1.1.3`、`v1.1.3 => v1.2.0` と揃っていない。IO_Bit に合わせて `vX.Y.Z => vA.B.C` の形にする。
5. タグ
   - 前回と同じ形式 (`v1.1.3` のように v 付き、3 桁) でタグを打ち、ブランチと一緒に公開する。
6. GitHub リリース
   - 過去のリリースがある (最新は `v1.1.3` "delete tag function")。
   - タイトルは変更の要約にし、利用者が読んで分かる言葉で書く。
7. Wiki
   - Wiki はまだ作られていない (`git@github.com:yoya/IO_ICC.wiki.git` が無い)。作るには GitHub の Web 画面で最初のページを作る必要がある。それまでこのステップは無い。
8. 反映の確認
   - リリースが最新版として見えていること、Packagist (`yoya/io_icc`) に新しい版が反映されていることを確かめる。

# リリースの経緯

- `v1.2.0` のタグは手元にだけあり、公開されていない (コミット `v1.1.3 => v1.2.0` は公開済み)。GitHub リリースも無く、Packagist の最新は `v1.1.3`。
  このタグを公開するか、打ち直すか、次の番号に進むかは持ち主が決める。勝手に push しない。

公開する文書 (コミットメッセージ、リリースノート、README、Wiki) には、`./tmp/` 由来のファイル名や中身を入れない。

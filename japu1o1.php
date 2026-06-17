<?php include ("header.php"); ?>
<?php

$questions = [

    // 家族・ペット（Family・Pets）
    "ご家族について話してください。",
    "____さんの家族は、どんな家族ですか。",
    "週末に家族とどんなことをしますか。",
    "家族とよくいっしょに出かけますか。",
    "お父さん／お母さんは、どんな人ですか。",
    "お父さん／お母さんは、どんな仕事をしていますか。",
    "きょうだいは、なかがいいですか。",
    "お兄さん／お姉さん／弟さん／妹さんは、日本語を勉強していますか。",
    "家で何かおてつだいをしますか。",
    "ペットをかっていますか。",
    "どんなしゅるいの犬／ねこですか。",

    // 友だち（Friends）
    "なかがいい友だちについて、話してください。",
    "その友だちとどこで知り合いましたか。",
    "その友だちはどんな人ですか。",
    "友だちとどんなことをしてあそびますか。",
    "友だちといっしょに勉強しますか。",
    "日本人の友だちがいますか。",
    "日本人のりゅう学生が家にとまったことがありますか。",
    "日本人のりゅう学生が来て、いいこと／わるいことはありましたか。",

    // 学校・勉強（School・Study）
    "どうやって学校まで行きますか。",
    "学校で勉強のほかにどんなことをしていますか。",
    "今年の学校生活はどうですか。",
    "学校で、一番楽しいこと／たいへんなことは何ですか。",
    "今年、どんな科目を勉強していますか。",
    "とくいな／にがてな科目は何ですか。",
    "今年の勉強はどうですか。",
    "勉強でつかれた時はどうしますか。",
    "リラックスするために、何をしていますか。",
    "毎日家でどのくらい勉強していますか。",
    "勉強などでストレスがあるときは、どうしていますか。",
    "日本語の勉強で何が一番たいへんですか。",
    "どうして日本語を勉強していますか。",
    "どのくらい／何年間、日本語を勉強していますか。",
    "日本語を勉強して、どんなことを学びましたか。",
    "日本のどんな文化にきょうみがありますか。",
    "日本語の勉強でどんなトピックがおもしろかったですか。",
    "学校の勉強以外に日本語を使うことがありますか。",
    "日本語が上手になるために、何がひつようだと思いますか。"
];


$randomQuestion = $questions[array_rand($questions)];
?>

<br>
<head>
    <title>jap u1o1 practice</title>
</head>
<body>
<h2>jap u1o1 practice</h2>
<p>use a chromium based browser for speech to work</p>
<br>
<h2><?php echo $randomQuestion; ?></h2>

<form method="post">
    <button class="btn btn-primary" type="submit">New Question</button>
</form>
<br>
<button class="btn btn-secondary" onclick="speak()">Play Audio</button>

<script>
function speak() {
    var text = "<?php echo $randomQuestion; ?>";
    var utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = "ja-JP";
    speechSynthesis.speak(utterance);
}
</script>

</body>

<?php include ("footer.php"); ?>
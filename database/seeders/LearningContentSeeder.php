<?php

namespace Database\Seeders;

use App\Models\PracticePackage;
use App\Models\Program;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LearningContentSeeder extends Seeder
{
    public function run(): void
    {
        if (Program::exists()) {
            return;
        }
        DB::transaction(function () {
            $japanese = Program::create(['title' => 'Japanese Language Practice', 'description' => 'Build your Japanese, from everyday words to confident conversations.', 'cover' => 'japanese', 'status' => 'active']);
            $english = Program::create(['title' => 'English for Everyday Life', 'description' => 'Make yourself understood. Practice essential English for real situations.', 'cover' => 'english', 'status' => 'active']);
            $work = Program::create(['title' => 'Professional Skills', 'description' => 'Grow practical knowledge for a safer, more effective workplace.', 'cover' => 'workplace', 'status' => 'active']);
            $vocabulary = $japanese->modules()->create(['title' => 'Basic Vocabulary', 'description' => 'The words you will use every day. JLPT N5 foundations.', 'status' => 'active']);
            $grammar = $japanese->modules()->create(['title' => 'Everyday Grammar', 'description' => 'Simple sentence patterns for everyday conversations.', 'status' => 'active']);
            $conversation = $english->modules()->create(['title' => 'Everyday Conversations', 'description' => 'Introductions, everyday routines, and finding your way.', 'status' => 'active']);
            $safety = $work->modules()->create(['title' => 'Workplace Safety', 'description' => 'Essential safety awareness at work.', 'status' => 'active']);
            $p1 = $vocabulary->packages()->create(['title' => 'N5 Vocabulary · Part 1', 'description' => 'Practice everyday greetings, numbers, and essential nouns.', 'duration_minutes' => 15, 'passing_score' => 70, 'show_explanations' => true, 'status' => 'active']);
            $p2 = $vocabulary->packages()->create(['title' => 'N5 Vocabulary · Part 2', 'description' => 'Build your knowledge of time, places, and daily activities.', 'duration_minutes' => 15, 'passing_score' => 70, 'show_explanations' => true, 'status' => 'active']);
            $p3 = $grammar->packages()->create(['title' => 'N5 Grammar · Part 1', 'description' => 'Choose the right particles and complete simple sentences.', 'duration_minutes' => 20, 'passing_score' => 70, 'show_explanations' => true, 'status' => 'active']);
            $p4 = $conversation->packages()->create(['title' => 'Everyday English · Part 1', 'description' => 'Choose natural responses in everyday English conversations.', 'duration_minutes' => 10, 'passing_score' => 70, 'show_explanations' => true, 'status' => 'active']);
            $p5 = $safety->packages()->create(['title' => 'Safety Essentials · Part 1', 'description' => 'Practice basic workplace safety decisions.', 'duration_minutes' => 10, 'passing_score' => 80, 'show_explanations' => true, 'status' => 'active']);
            $this->questions($p1, [
                ['「おはようございます」はいつ使いますか？', 'In the morning', 'In the afternoon', 'At night', 'When saying goodbye', 'A', 'おはようございます is a polite morning greeting.'],
                ['What does 「ありがとう」 mean?', 'Excuse me', 'Thank you', 'Good morning', 'See you', 'B', 'ありがとう means thank you. The polite form is ありがとうございます.'],
                ['「水」の読み方はどれですか？', 'ひ', 'やま', 'みず', 'そら', 'C', '水 is read みず (mizu) and means water.'],
                ['What is the meaning of 「学校」?', 'Station', 'Hospital', 'Company', 'School', 'D', '学校 (がっこう) means school.'],
                ['Choose the Japanese word for "book".', '本（ほん）', '花（はな）', '犬（いぬ）', '雨（あめ）', 'A', '本 (ほん) means book.'],
                ['「先生」の意味はどれですか？', 'Student', 'Teacher', 'Friend', 'Doctor only', 'B', '先生 (せんせい) is used for teachers and as a respectful title for some professionals.'],
                ['Which number is 「三」?', 'One', 'Two', 'Three', 'Four', 'C', '三 is read さん (san) and means three.'],
                ['「ねこ」は英語で何ですか？', 'Dog', 'Bird', 'Fish', 'Cat', 'D', 'ねこ means cat.'],
                ['What does 「すみません」 commonly mean?', 'Excuse me / Sorry', 'Good night', 'Welcome home', 'Congratulations', 'A', 'すみません is used to apologize or politely get attention.'],
                ['Choose the word for "friend".', '家族（かぞく）', '友達（ともだち）', '会社（かいしゃ）', '仕事（しごと）', 'B', '友達 means friend.'],
                ['「りんご」は何ですか？', 'Orange', 'Banana', 'Apple', 'Grape', 'C', 'りんご means apple.'],
                ['What does 「日本」 mean?', 'Korea', 'China', 'Thailand', 'Japan', 'D', '日本 is read にほん or にっぽん and means Japan.'],
            ]);
            $this->questions($p2, [
                ['What does 「今日」 mean?', 'Today', 'Tomorrow', 'Yesterday', 'Every day', 'A', '今日 (きょう) means today.'],
                ['「駅」の読み方はどれですか？', 'いえ', 'えき', 'みち', 'まち', 'B', '駅 is read えき and means station.'],
                ['Choose the word for "to eat".', '飲む', '読む', '食べる', '書く', 'C', '食べる (たべる) means to eat.'],
                ['What does 「明日」 mean?', 'Yesterday', 'Today', 'Morning', 'Tomorrow', 'D', '明日 (あした) means tomorrow.'],
                ['「大きい」の意味はどれですか？', 'Big', 'Small', 'Old', 'New', 'A', '大きい (おおきい) means big.'],
                ['What does 「電車」 mean?', 'Car', 'Train', 'Bicycle', 'Airplane', 'B', '電車 (でんしゃ) means train.'],
                ['Choose the word for "to read".', '聞く', '話す', '読む', '見る', 'C', '読む (よむ) means to read.'],
                ['「毎日」の意味はどれですか？', 'Every week', 'Every month', 'Every year', 'Every day', 'D', '毎日 (まいにち) means every day.'],
            ]);
            $this->questions($p3, [
                ['わたし（　）学生です。', 'は', 'を', 'に', 'で', 'A', 'は marks the topic: わたしは学生です。'],
                ['学校（　）行きます。', 'を', 'に', 'が', 'と', 'B', 'に marks a destination with 行きます.'],
                ['本（　）読みます。', 'は', 'に', 'を', 'で', 'C', 'を marks the direct object of 読みます.'],
                ['図書館（　）勉強します。', 'を', 'に', 'が', 'で', 'D', 'で marks the location of an activity.'],
                ['これはわたし（　）かばんです。', 'の', 'を', 'に', 'で', 'A', 'の indicates possession: my bag.'],
                ['Choose the polite negative of 食べます.', '食べました', '食べません', '食べましょう', '食べて', 'B', '食べません is the polite non-past negative form.'],
                ['きのう映画を（　）。', '見ます', '見ません', '見ました', '見ましょう', 'C', 'きのう refers to yesterday, so use past tense 見ました.'],
                ['あの人は先生です（　）。', 'を', 'に', 'で', 'か', 'D', 'か at the end makes a polite question.'],
            ]);
            $this->questions($p4, [
                ['"Nice to meet you." Choose the best response.', 'Nice to meet you too.', 'I am going home.', 'It is raining.', 'At seven o\'clock.', 'A', 'Nice to meet you too is a natural reply when introduced to someone.'],
                ['Choose the correct sentence.', 'She work in Tokyo.', 'She works in Tokyo.', 'She working Tokyo.', 'She are work in Tokyo.', 'B', 'Use works with the third-person singular subject she in the simple present.'],
                ['"How often do you study?" Choose the best response.', 'In the library.', 'With my friend.', 'Every day.', 'English.', 'C', 'How often asks about frequency. Every day answers that question.'],
                ['Choose a polite request.', 'Give water.', 'Water now.', 'You bring water.', 'Could I have some water, please?', 'D', 'Could I have ... please? is a polite request.'],
                ['"Where is the station?" Choose the best response.', 'Next to the bank.', 'At nine o\'clock.', 'Very often.', 'By train.', 'A', 'Next to the bank describes a location.'],
                ['I have lived here (　) 2020.', 'for', 'since', 'during', 'at', 'B', 'Since marks the starting point of a period that continues to the present.'],
            ]);
            $this->questions($p5, [
                ['You notice a wet floor. What should you do first?', 'Warn others and report the hazard', 'Ignore it', 'Run across it', 'Cover it with paper', 'A', 'Warn others and report or address the hazard according to workplace procedures.'],
                ['Why should emergency exits remain clear?', 'To store boxes', 'To allow safe evacuation', 'To reduce lighting', 'To reduce noise', 'B', 'Clear emergency exits allow people to evacuate without obstruction.'],
                ['You find damaged equipment. What is the best action?', 'Keep using it', 'Repair it without training', 'Stop using it and report it', 'Hide it', 'C', 'Do not use damaged equipment. Follow the reporting procedure.'],
                ['Before a new task, you should:', 'Skip instructions', 'Rush to finish', 'Borrow unknown tools', 'Review instructions and required protection', 'D', 'Understand the procedure and required protective equipment before beginning.'],
            ]);
            $vocabulary->materials()->create(['title' => 'Everyday Greetings & Expressions', 'reading_minutes' => 6, 'status' => 'active', 'content' => "## Start with a greeting\n\nSmall expressions make a big difference. These are some of the first phrases you will use in Japanese.\n\n| Japanese | Reading | Meaning |\n| --- | --- | --- |\n| おはようございます | Ohayou gozaimasu | Good morning |\n| こんにちは | Konnichiwa | Hello / Good afternoon |\n| こんばんは | Konbanwa | Good evening |\n| ありがとうございます | Arigatou gozaimasu | Thank you |\n| すみません | Sumimasen | Excuse me / Sorry |\n\n## Polite and casual speech\n\nUse **おはようございます** with teachers, colleagues, or people you do not know well. With close friends, **おはよう** is common.\n\n> すみません can be both an apology and a polite way to get someone\'s attention.\n\n## A short conversation\n\n**A:** おはようございます。\n\n**B:** おはようございます。お元気ですか。\n\n**A:** はい、元気です。ありがとうございます。\n\n## Before you practice\n\n- Read each phrase aloud.\n- Match each greeting to a time of day.\n- Try the N5 Vocabulary Part 1 practice package.\n"]);
            $vocabulary->materials()->create(['title' => 'Numbers, Time & Daily Words', 'reading_minutes' => 8, 'status' => 'active', 'content' => "## Numbers from one to ten\n\n| Number | Japanese | Reading |\n| --- | --- | --- |\n| 1 | 一 | いち |\n| 2 | 二 | に |\n| 3 | 三 | さん |\n| 4 | 四 | よん / し |\n| 5 | 五 | ご |\n| 6 | 六 | ろく |\n| 7 | 七 | なな / しち |\n| 8 | 八 | はち |\n| 9 | 九 | きゅう / く |\n| 10 | 十 | じゅう |\n\n## Talking about days\n\n- 今日（きょう）: today\n- 明日（あした）: tomorrow\n- 昨日（きのう）: yesterday\n- 毎日（まいにち）: every day\n\n**Example:** 毎日、日本語を勉強します。\n\nI study Japanese every day.\n"]);
            $grammar->materials()->create(['title' => 'Your First Japanese Sentences', 'reading_minutes' => 7, 'status' => 'active', 'content' => "## The basic pattern\n\n**Topic は Description です。**\n\nわたしは学生です。\n\nI am a student.\n\n## Four useful particles\n\n| Particle | Function | Example |\n| --- | --- | --- |\n| は | Topic | わたしは学生です。 |\n| を | Direct object | 本を読みます。 |\n| に | Destination | 学校に行きます。 |\n| で | Place of an activity | 図書館で勉強します。 |\n\n## Make a question\n\nAdd **か** at the end of a polite sentence.\n\n田中さんは先生ですか。\n\nIs Tanaka a teacher?\n"]);
            $conversation->materials()->create(['title' => 'Introducing Yourself in English', 'reading_minutes' => 5, 'status' => 'active', 'content' => "## Keep it simple\n\nA short introduction can include your name, where you are from, and what you do.\n\n> Hi, I\'m Aiko. I\'m from Japan. I\'m a student. Nice to meet you!\n\n## Useful phrases\n\n- My name is ...\n- I\'m from ...\n- I work as a ...\n- I\'m interested in ...\n- Nice to meet you.\n\n## Ask a follow-up question\n\n**What do you do?** asks about someone\'s work or main activity.\n\n**Where are you from?** asks about their home country or hometown.\n"]);
            $safety->materials()->create(['title' => 'A Safer Working Day', 'reading_minutes' => 4, 'status' => 'active', 'content' => "## Before starting work\n\n- Review the task instructions.\n- Check your equipment.\n- Locate emergency exits.\n- Report hazards promptly.\n\nAlways follow your workplace\'s specific procedures and training.\n"]);
        });
    }

    private function questions(PracticePackage $package, array $rows): void
    {
        foreach ($rows as $row) {
            $package->questions()->create(array_combine(['question', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer', 'explanation'], $row) + ['status' => 'active']);
        }
    }
}

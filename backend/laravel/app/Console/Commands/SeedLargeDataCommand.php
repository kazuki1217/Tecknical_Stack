<?php

namespace App\Console\Commands;

use Database\Seeders\LargeDataSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 投稿件数を受け取り、性能検証用Seederを実行するArtisanコマンド。
 */
class SeedLargeDataCommand extends Command
{
    /**
     * 投稿件数だけを利用者が指定し、chunk幅などの実装詳細はSeeder側で管理する。
     *
     * @var string
     */
    protected $signature = 'db:seed-large
        {posts=300000 : 投入する投稿件数}';

    /** @var string */
    protected $description = '性能検証用の大量ダミーデータをchunk単位のバルクINSERTで投入する';

    /**
     * 入力値と実行環境を検証してから、大量データSeederを実行する。
     * 例: `php artisan db:seed-large 10000`を実行すると、1万投稿の投入をSeederへ依頼する。
     */
    public function handle(LargeDataSeeder $seeder): int
    {
        if (app()->environment('production')) {
            Log::warning('大量データSeederの本番実行を拒否しました。');
            $this->error('このコマンドは本番環境では実行できません。');

            return self::FAILURE;
        }

        $posts = $this->argument('posts');

        if (! is_string($posts) || ! ctype_digit($posts) || (int) $posts < 1) {
            $this->error('投稿件数には1以上の整数を指定してください。');

            return self::FAILURE;
        }

        try {
            $this->info("大量ダミーデータの投入を開始します（投稿 {$posts}件）。");
            $seeder->run((int) $posts);
        } catch (Throwable $e) {
            $this->error('大量データの投入に失敗しました。詳細はLaravelログを確認してください。');

            return self::FAILURE;
        }

        $this->info('大量ダミーデータの投入が完了しました。');

        return self::SUCCESS;
    }
}

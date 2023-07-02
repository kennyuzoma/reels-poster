<?php

namespace App\Jobs;

use App\Helpers\ConsoleMessage;
use App\Models\Post;
use App\Notifications\SocialPostProcessing;
use App\Notifications\SocialPostFailed;
use App\Notifications\SocialPostSuccessful;
use App\Services\Instagram\InstagramPublisher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessSocialPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 3600;

    private $servicePublisher = [
        'instagram' => InstagramPublisher::class
    ];

    /**
     * The post instance.
     *
     * @var \App\Models\Post
     */
    public $post;

    private $consoleMessage;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Post $post)
    {
        $this->consoleMessage = new ConsoleMessage();
        $this->post = $post;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $this->post->notify(new SocialPostProcessing());

            (new $this->servicePublisher[$this->post->account->service]())
                ->setPost($this->post)
                ->{$this->post->type}();

            $this->post->notify(new SocialPostSuccessful());

        } catch (\Exception $e) {
            $this->post->notify(new SocialPostFailed($e->getMessage()));
            throw $e;
//            $this->message($e->getMessage(), true);
        }
    }

    private function message($text, $type = 'error', $exceptionMessage = false)
    {
        if (app()->runningInConsole()) {
            if ($exceptionMessage) {
                $text = 'There was an error: ' . $text;
            }
//            $this->consoleMessage->error($text);
        }
            throw new \Exception($text);

    }
}

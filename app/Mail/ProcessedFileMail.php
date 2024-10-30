<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProcessedFileMail extends Mailable
{
    use Queueable, SerializesModels;

    public $filePath;
    public $user;

    public function __construct($filePath, $user)
    {
        $this->filePath = $filePath;
        $this->user = $user;
    }

    public function build()
    {
        return $this->subject('Processed File')
                    ->view('processed_file') // Create a view for email content
                    ->attach($this->filePath, [
                        'as' => 'Processed_Files_Data.xlsx',
                        'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ]);
    }
}

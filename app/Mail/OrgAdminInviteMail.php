<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrgAdminInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $setUrl;

    public function __construct(User $user, string $setUrl)
    {
        $this->user = $user;
        $this->setUrl = $setUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Set Your Password - Organization Admin Invite',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: "
                <h2>Hello {$this->user->name},</h2>
                <p>You have been invited to set up your admin account.</p>
                <p>Please click the button below to choose your password:</p>
                <p>
                    <a href='{$this->setUrl}' style='background: #4F46E5; color: white; padding: 10px 18px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                        Set Password
                    </a>
                </p>
                <p>If the button doesn't work, copy this link into your browser:<br> {$this->setUrl}</p>
            ",
        );
    }
}
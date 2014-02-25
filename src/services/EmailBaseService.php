<?php

/**
 * Base Email Class
 *
 * This class provides a base for different email implementations
 *
 * @author Kim Rowan
 */
abstract class EmailBaseService
{
    /**
     * The SwiftMailer object
     */
    protected $mailer;

    /**
     * The SwiftMessage object
     */
    protected $message;

    abstract public function parseEmail();

    /**
     * Make a message to be sent later
     *
     * @param array $recipients An array of email addresses
     */
    public function __construct($recipients) {
        $transport = Swift_SendmailTransport::newInstance('/usr/sbin/sendmail -bs');
        $this->mailer  = \Swift_Mailer::newInstance($transport);
        $this->message = \Swift_Message::newInstance();
        // TODO allow override so on dev all emails go to one address
        $this->message->setTo($recipients);
        $this->message->setFrom("info@joind.in");
    }

    /**
     * Set the body of the message
     */
    public function setBody($body) {
        $this->message->setBody($body);
        return $this;
    }

    /**
     * Send the email that we created
     */
    protected function dispatchEmail()
    {
        $success = $this->mailer->send($this->message);
    }

}

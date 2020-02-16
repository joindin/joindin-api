<?php

namespace Joindin\Api\Test\Service;

use Joindin\Api\Model\TalkModel;
use Joindin\Api\Service\TalkCommentEmailService;
use PHPUnit\Framework\TestCase;

final class TalkCommentEmailServiceTest extends Testcase
{
    protected $config = [
        'email' => [
            "from" => "test@joind.in",
            'smtp' => [
                'host' => 'localhost',
                'port' => 25,
                'username' => 'username',
                'password' => 'ChangeMeSeymourChangeMe',
                'security' => null,
            ],
        ],
    ];

    /**
     * Check that we can create the service
     */
    public function testCreateService()
    {
        $recipients = ["test@joind.in"];
        $talk = new TalkModel(["talk_title" => "sample talk"]);
        $comment = ["comments" => [["comment" => "test comment", "rating" => 3]]];

        $service = new TalkCommentEmailService($this->config, $recipients, $talk, $comment);
        $this->assertInstanceOf(TalkCommentEmailService::class, $service);
    }

    /**
     * Test email override
     */
    public function testCreateServiceWithEmailRedirect()
    {
        $config = $this->config;
        $config["email"]["forward_all_to"] = "blackhole@joind.in";
        $recipients = ["test@joind.in"];
        $talk = new TalkModel(["talk_title" => "sample talk"]);
        $comment = ["comments" => [["comment" => "test comment", "rating" => 3]]];

        $service = new TalkCommentEmailService($config, $recipients, $talk, $comment);
        $this->assertEquals(["blackhole@joind.in"], $service->getRecipients());
    }

    /**
     * Test replacements work into templates
     */
    public function testTemplateReplacements()
    {
        $recipients = ["test@joind.in"];
        $talk = new TalkModel(["talk_title" => "sample talk"]);
        $comment = ["comments" => [["comment" => "test comment", "rating" => 3]]];

        $service = new TalkCommentEmailService($this->config, $recipients, $talk, $comment);
        $service->templatePath = __DIR__ . '/../../src/View/emails/';

        $template = "testTemplate.md";
        $replacements = ["cat" => "Camel", "mat" => "magic carpet"];
        $message = $service->parseEmail($template, $replacements);
        $expected = "The Camel sat on the magic carpet


----
Questions? Comments?  Get in touch: [feedback@joind.in](mailto:feedback@joind.in) or [@joindin](http://twitter.com/joindin) on twitter.

";

        $this->assertEquals($message, $expected);
    }

    /**
     * Should be able to get markdown to HTML
     */
    public function testMarkdownTransform()
    {
        $markdown = "A *sunny* day";
        $recipients = ["test@joind.in"];
        $talk = new TalkModel(["talk_title" => "sample talk"]);
        $comment = ["comments" => [["comment" => "test comment", "rating" => 3]]];

        $service = new TalkCommentEmailService($this->config, $recipients, $talk, $comment);

        $html = $service->markdownToHtml($markdown);
        $this->assertEquals(
            $html,
            "<p>A <em>sunny</em> day</p>\n"
        );
    }
}

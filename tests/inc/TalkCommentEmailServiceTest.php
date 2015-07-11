<?php

namespace JoindinTest\inc;

require_once __DIR__ . '/../../src/services/TalkCommentEmailService.php';

class TalkCommentEmailServiceTest extends \PHPUnit_Framework_Testcase
{
    /**
     * Check that we can create the service
     *
     * @test
     */
    public function createService()
    {
        $config = array("email" => array("from" => "test@joind.in"));
        $recipients = array("test@joind.in");
        $talk = array("talks" => array(array("talk_title" => "sample talk")));
        $comment = array("comments" => array(array("comment" => "test comment", "rating" => 3)));

        $service = new \TalkCommentEmailService($config, $recipients, $talk, $comment);
        $this->assertInstanceOf('TalkCommentEmailService', $service);
    }

    /**
     * Test email override
     *
     * @test
     */
    public function createServiceWithEmailRedirect()
    {
        $config = array("email" => array("from" => "test@joind.in", "forward_all_to" => "blackhole@joind.in"));
        $recipients = array("test@joind.in");
        $talk = array("talks" => array(array("talk_title" => "sample talk")));
        $comment = array("comments" => array(array("comment" => "test comment", "rating" => 3)));

        $service = new \TalkCommentEmailService($config, $recipients, $talk, $comment);
        $this->assertEquals($service->getRecipients(), array("blackhole@joind.in"));
    }

    /**
     * Test replacements work into templates
     *
     * @test
     */
    public function templateReplacements()
    {
        $config = array("email" => array("from" => "test@joind.in"));
        $recipients = array("test@joind.in");
        $talk = array("talks" => array(array("talk_title" => "sample talk")));
        $comment = array("comments" => array(array("comment" => "test comment", "rating" => 3)));

        $service = new \TalkCommentEmailService($config, $recipients, $talk, $comment);
        $service->templatePath = __DIR__ . '/../../src/views/emails/';

        $template = "testTemplate.md";
        $replacements = array("cat" => "Camel", "mat" => "magic carpet");
        $message = $service->parseEmail($template, $replacements);
        $expected = "The Camel sat on the magic carpet


----
Questions? Comments?  Get in touch: [feedback@joind.in](mailto:feedback@joind.in) or [@joindin](http://twitter.com/joindin) on twitter.

";

        $this->assertEquals($message, $expected);
    }

    /**
     * Should be able to get markdown to HTML
     *
     * @test
     */
    public function markdownTransform()
    {
        $markdown = "A *sunny* day";
        
        $config = array("email" => array("from" => "test@joind.in"));
        $recipients = array("test@joind.in");
        $talk = array("talks" => array(array("talk_title" => "sample talk")));
        $comment = array("comments" => array(array("comment" => "test comment", "rating" => 3)));

        $service = new \TalkCommentEmailService($config, $recipients, $talk, $comment);

        $html = $service->markdownToHtml($markdown);
        $this->assertEquals($html, "<p>A <em>sunny</em> day</p>
");
    }
}

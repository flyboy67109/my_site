<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testSomething()
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $crawler = $client->submitForm('form_submit',[
            'form[name]'    =>  "test name",
            'form[email]'   =>  "test@test.com",
            'form[message]' =>  "test message"
        ]);

        var_dump($client->getResponse()->setContent());exit;
//        $this->assertResponseIsSuccessful();
//        $this->assertSelectorTextContains('h1', 'Hello World');
    }
}

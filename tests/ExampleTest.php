<?php

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Test that login.php file exists and loads without errors
     */
    public function testLoginFileExists(): void
    {
        $this->assertFileExists('login.php');
        
        ob_start();
        include 'login.php';
        $output = ob_get_clean();
        
        $this->assertNotEmpty($output);
    }

    /**
     * Test that index.php file exists and loads without errors
     */
    public function testIndexFileExists(): void
    {
        $this->assertFileExists('index.php');
        
        ob_start();
        include 'index.php';
        $output = ob_get_clean();
        
        $this->assertNotEmpty($output);
    }

    /**
     * Test that login.php contains some form of HTML
     */
    public function testLoginHasHTML(): void
    {
        ob_start();
        include 'login.php';
        $output = ob_get_clean();
        
        // Check if it has basic HTML structure
        $this->assertTrue(
            strpos($output, '<html') !== false || 
            strpos($output, '<form') !== false ||
            strpos($output, '<!DOCTYPE') !== false,
            'Login page should contain HTML content'
        );
    }

    /**
     * Test that index.php contains some form of HTML
     */
    public function testIndexHasHTML(): void
    {
        ob_start();
        include 'index.php';
        $output = ob_get_clean();
        
        // Check if it has basic HTML structure
        $this->assertTrue(
            strpos($output, '<html') !== false || 
            strpos($output, '<body') !== false ||
            strpos($output, '<!DOCTYPE') !== false,
            'Index page should contain HTML content'
        );
    }

    /**
     * Test basic PHP functionality
     */
    public function testBasicPHPWorks(): void
    {
        $this->assertTrue(true);
        $this->assertEquals(4, 2 + 2);
        $this->assertIsString("hello");
        $this->assertIsArray([1, 2, 3]);
    }

    /**
     * Test that files don't have syntax errors by including them
     */
    public function testFilesHaveNoSyntaxErrors(): void
    {
        // If these includes fail, the test will fail
        $this->assertTrue(true); // This will pass if no syntax errors occur
        
        ob_start();
        include 'login.php';
        ob_end_clean();
        
        ob_start();
        include 'index.php';
        ob_end_clean();
        
        $this->assertTrue(true);
    }
}
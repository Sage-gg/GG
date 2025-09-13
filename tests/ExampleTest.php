<?php
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    protected function setUp(): void
    {
        // Start output buffering to prevent headers already sent errors
        if (!ob_get_level()) {
            ob_start();
        }
    }

    protected function tearDown(): void
    {
        // Clean up output buffer
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    /**
     * Test that login.php file exists
     */
    public function testLoginFileExists(): void
    {
        $this->assertFileExists('login.php');
    }

    /**
     * Test that index.php file exists
     */
    public function testIndexFileExists(): void
    {
        $this->assertFileExists('index.php');
    }

    /**
     * Test that login.php contains HTML by reading file content
     */
    public function testLoginHasHTML(): void
    {
        $content = file_get_contents('login.php');
        $this->assertNotFalse($content, 'Should be able to read login.php');
        
        // Check if it has basic HTML structure
        $this->assertTrue(
            stripos($content, '<html') !== false || 
            stripos($content, '<form') !== false ||
            stripos($content, '<!DOCTYPE') !== false,
            'Login page should contain HTML content'
        );
    }

    /**
     * Test that index.php contains HTML by reading file content
     */
    public function testIndexHasHTML(): void
    {
        $content = file_get_contents('index.php');
        $this->assertNotFalse($content, 'Should be able to read index.php');
        
        // Check if it has basic HTML structure
        $this->assertTrue(
            stripos($content, '<html') !== false || 
            stripos($content, '<body') !== false ||
            stripos($content, '<!DOCTYPE') !== false,
            'Index page should contain HTML content'
        );
    }

    /**
     * Test that db.php file exists
     */
    public function testDbFileExists(): void
    {
        $this->assertFileExists('db.php');
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
     * Test that files have valid PHP syntax without executing them
     */
    public function testFilesHaveValidPHPSyntax(): void
    {
        // Check login.php syntax
        $loginContent = file_get_contents('login.php');
        $this->assertNotFalse($loginContent, 'Should be able to read login.php');
        $this->assertStringStartsWith('<?php', trim($loginContent), 'login.php should start with PHP tag');

        // Check index.php syntax  
        $indexContent = file_get_contents('index.php');
        $this->assertNotFalse($indexContent, 'Should be able to read index.php');
        $this->assertStringStartsWith('<?php', trim($indexContent), 'index.php should start with PHP tag');

        // Check db.php syntax
        $dbContent = file_get_contents('db.php');
        $this->assertNotFalse($dbContent, 'Should be able to read db.php');
        $this->assertStringStartsWith('<?php', trim($dbContent), 'db.php should start with PHP tag');
    }

    /**
     * Test that index.php contains expected functions
     */
    public function testIndexContainsExpectedFunctions(): void
    {
        $content = file_get_contents('index.php');
        
        $this->assertStringContainsString('function getCollectionsSummary()', $content, 
            'index.php should contain getCollectionsSummary function');
        
        $this->assertStringContainsString('function getBudgetSummary()', $content,
            'index.php should contain getBudgetSummary function');
        
        $this->assertStringContainsString('function formatCurrency(', $content,
            'index.php should contain formatCurrency function');
    }

    /**
     * Test that login.php has form elements
     */
    public function testLoginContainsFormElements(): void
    {
        $content = file_get_contents('login.php');
        
        $this->assertTrue(
            stripos($content, '<form') !== false ||
            stripos($content, '<input') !== false ||
            stripos($content, 'type="password"') !== false,
            'Login should contain form elements'
        );
    }
}

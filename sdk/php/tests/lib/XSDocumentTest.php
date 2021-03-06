<?php
require_once dirname(__FILE__) . '/../../lib/XSDocument.class.php';
require_once dirname(__FILE__) . '/../../lib/XSFieldScheme.class.php';

/**
 * Test class for XSDocument.
 * Generated by PHPUnit on 2011-09-16 at 11:39:10.
 */
class XSDocumentTest extends PHPUnit_Framework_TestCase
{
	protected static $data, $data_gbk;

	/** 	
	 * @var XSDocument
	 */
	protected $doc1, $doc2, $doc3, $doc4;

	public static function setUpBeforeClass(): void
	{
		self::$data = array(
			'pid' => 1234,
			'subject' => "Hello, 测试标题",
			'message' => "您好，这儿是真正的测试内容\n另起一行用英文\n\nHello, the world!",
			'chrono' => time(),
		);
		self::$data_gbk = XS::convert(self::$data, 'GBK', 'UTF-8');
	}

	public static function tearDownAfterClass(): void
	{
		
	}

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp(): void
	{
		// doc1: input-UTF-8, index-doc
		$this->doc1 = new XSDocument('UTF-8');
		$this->doc1->setFields(self::$data);

		// doc2: input-GBK, index-doc
		$this->doc2 = new XSDocument(self::$data_gbk, 'GBK');

		// doc3: output-UTF8, search-doc
		$buf = pack('IIIif', 41, 1, 0, 98, 0.98);
		$this->doc3 = new XSDocument($buf);
		$this->doc3->setFields(self::$data);

		// doc4: output-GBK, search-doc
		$buf = pack('IIIif', 21, 2, 2, 69, 0.69);
		$this->doc4 = new XSDocument($buf, 'GBK');
		$this->doc4->setFields(self::$data);
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown(): void
	{
		
	}

	public function test__get()
	{
		$this->assertNull($this->doc1->subject2);
		$this->assertEquals(self::$data['subject'], $this->doc1->subject);
		$this->assertEquals(self::$data['subject'], $this->doc2->subject);
		$this->assertEquals(self::$data['subject'], $this->doc3->subject);
		$this->assertEquals(self::$data_gbk['subject'], $this->doc4->subject);
		$this->assertEquals(self::$data_gbk['subject'], $this->doc4->f('subject'));
	}

	public function test__set()
	{
		$this->assertNotNull($this->doc1->subject);
		$this->doc1->subject = null;
		$this->assertNull($this->doc1->subject);

		$this->doc1->subject = '换个标题';
		$this->doc2->subject = XS::convert($this->doc1->subject, 'GBK', 'UTF-8');
		$this->assertEquals($this->doc1->subject, $this->doc2->subject);

		$this->doc1->setField('subject', 'Another');
		$this->assertEquals('Another', $this->doc1->subject);
	}

	public function test__call1()
	{
		$this->expectException(XSException::class);
		$this->expectExceptionMessage('Call to undefined method `XSDocument::docid()\'');
		$this->doc1->docid();
	}

	public function test__call2()
	{
		$this->expectException(XSException::class);
		$this->expectExceptionMessage('Call to undefined method `XSDocument::docid2()\'');
		$this->doc3->docid();
		$this->doc3->docid2();
	}

	public function test__call()
	{
		$this->assertEquals(41, $this->doc3->docid());
		$this->assertEquals(21, $this->doc4->docid());

		$this->assertEquals(1, $this->doc3->rank());
		$this->assertEquals(0, $this->doc3->ccount());
		$this->assertEquals(98, $this->doc3->percent(), '', 0.01);
	}

	public function testGetCharset()
	{
		$this->assertNull($this->doc1->charset);
		$this->assertNull($this->doc3->charset);

		$this->assertEquals('GBK', $this->doc2->getCharset());
		$this->assertEquals('GBK', $this->doc4->getCharset());
	}

	public function testSetCharset()
	{
		$doc5 = new XSDocument(self::$data_gbk);
		$this->assertEquals(self::$data_gbk['subject'], $doc5->subject);

		$doc5->setCharset('GBK');
		$this->assertEquals(self::$data['subject'], $doc5->subject);

		$this->assertEquals(self::$data_gbk['subject'], $this->doc4->subject);
		$this->doc4->setCharset('UTF8');
		$this->assertEquals(self::$data['subject'], $this->doc4->subject);
	}

	public function testSetFields()
	{
		$this->assertNull($this->doc1->subject2);
		$this->assertEquals(self::$data['subject'], $this->doc1->subject);

		$this->doc1->setFields(array('subject' => 'Replaced', 'subject2' => self::$data['subject']));

		$this->assertEquals('Replaced', $this->doc1->subject);
		$this->assertEquals(self::$data['subject'], $this->doc1->subject2);
		$this->assertEquals(self::$data['pid'], $this->doc1->pid);
	}

	public function testGetAddTerms()
	{
		$this->assertNull($this->doc2->getAddTerms('subject2'));
		$this->assertNull($this->doc2->getAddTerms('subject'));

		$this->doc2->addTerm('subject', 'test1');
		$this->doc2->addTerm('subject', 'test2', 2);
		$this->doc2->addTerm('subject', 'test3', 3);
		$this->doc2->addTerm('subject', 'test2', 9);
		$this->doc2->addTerm('subject', XS::convert('GBK中文', 'GBK', 'UTF-8'));

		$chrono = new XSFieldMeta('chrono', array('type' => 'string'));
		$this->doc2->addTerm($chrono, '2010');
		$this->doc2->addTerm($chrono, '201009');
		$this->doc2->addTerm($chrono, '20100915');
		$this->doc2->addTerm($chrono, '2010');

		$this->assertNull($this->doc2->getAddTerms('subject2'));
		$this->assertEquals(self::$data['subject'], $this->doc2->subject);

		$expected = array('test1' => 1, 'test2' => 11, 'test3' => 3, 'GBK中文' => 1);
		$this->assertEquals($expected, $this->doc2->getAddTerms('subject'));

		$expected = array('2010' => 2, '201009' => 1, '20100915' => 1);
		$this->assertEquals($expected, $this->doc2->getAddTerms($chrono));
	}

	public function testGetAddIndex()
	{
		$this->assertNull($this->doc2->getAddIndex('subject2'));
		$this->assertNull($this->doc2->getAddIndex('subject'));

		$subject = new XSFieldMeta('subject');
		$this->doc2->addIndex('subject', 'hello the world');
		$this->doc2->addIndex($subject, XS::convert('您好世界', 'GBK', 'UTF-8'));

		$this->assertEquals(self::$data['subject'], $this->doc2->subject);
		$this->assertEquals("hello the world\n您好世界", $this->doc2->getAddIndex($subject));
	}

	public function testGetIterator()
	{
		$temp = array();
		foreach ($this->doc1 as $key => $value) {
			$temp[$key] = $value;
		}
		$this->assertEquals(self::$data, $temp);

		$temp = array();
		foreach ($this->doc2 as $key => $value) {
			$temp[$key] = $value;
		}
		$this->assertEquals(self::$data, $temp);
	}

	public function testOffsetExists()
	{
		$this->assertTrue(isset($this->doc2['subject']));
		$this->assertFalse(isset($this->doc2['subject2']));
	}

	public function testOffsetGet()
	{
		$this->assertEquals(self::$data['subject'], $this->doc2['subject']);
	}

	public function testOffsetSet()
	{
		$this->assertNull($this->doc2['subject2']);
		$this->doc2['subject2'] = 'foo';
		$this->assertNotNull($this->doc2['subject2']);
	}

	public function testOffsetUnset()
	{

		$this->assertNotNull($this->doc2['subject']);
		unset($this->doc2['subject']);
		$this->assertNull($this->doc2['subject']);
	}

	public function testAddTerm()
	{
		$xs = new XS(end($GLOBALS['fixIniData']));
		$doc = new XSDocument('UTF-8');
		$doc->pid = '20061016';
		$doc->subject = 'Hello, mazeyuan!';
		$doc->message = 'I love you forever!';

		// bool field 'date' (unused weight)
		$doc->addTerm('date', 'Y2006', 1000);
		$doc->addTerm('date', 'MD1016', 9999);

		// non-bool field (with stem), 'subject'
		$doc->addTerm('subject', '马明练');
		$doc->addTerm('subject', '马明练', 200);
		$doc->addTerm('subject', 'Twomice', 99);

		$xs->index->clean();
		$xs->index->add($doc);
		$xs->index->flushIndex();

		// wait for flushing
		sleep(3);

		$xs->search->setCharset('UTF-8');
		// test search result about terms
		$this->assertEquals(1, $xs->search->count('date:Y2006'));
		$this->assertEquals(1, $xs->search->count('date:md1016'));
		$this->assertEquals(0, $xs->search->count('subject:马明练'));
		$xs->search->setQuery(null)->addQueryTerm('subject', '马明练');
		$this->assertEquals(1, $xs->search->count());
		$this->assertEquals(1, $xs->search->count('subject:twomic'));
		$xs->index->clean();
		sleep(1);
	}

	public function testAddIndex()
	{
		$xs = new XS(end($GLOBALS['fixIniData']));
		$doc = new XSDocument('UTF-8');
		$doc->pid = '20061016';
		$doc->subject = 'Hello, mazeyuan!';
		$doc->message = 'I love you forever!';

		// bool field 'date' split by '/'
		$doc->addIndex('date', 'Y2006/M10/D16');

		// non-bool field (with stem), 'subject'
		$doc->addIndex('message', '你真是个小坏蛋');
		$doc->addIndex('subject', '标是附加文字');

		$xs->index->clean();
		$xs->index->add($doc);
		$xs->index->flushIndex();

		// wait for flushing
		sleep(3);

		$xs->search->setCharset('UTF-8');

		// test search result about indexed terms
		$this->assertEquals(1, $xs->search->count('date:Y2006/M10'));
		$this->assertEquals(1, $xs->search->count('date:D16'));
		$this->assertEquals(1, $xs->search->count('subject:附加文字'));
		$this->assertEquals(1, $xs->search->count('真是个小坏蛋'));
		$xs->index->clean();
		sleep(1);
	}

	public function testBeforeSubmit()
	{
		
	}

	public function testAfterSubmit()
	{
		
	}
}

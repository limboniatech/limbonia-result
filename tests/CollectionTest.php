<?php
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
  protected $aData =
  [
    [
      'stuff' => 'thing',
      'foo' => 'bar',
      'hi' => 'bye'
    ],
    [
      'stuff' => 'thing1',
      'foo' => 'bar1',
      'hi' => 'bye1'
    ],
    [
      'stuff' => 'thing2',
      'foo' => 'bar2',
      'hi' => 'bye2'
    ]
  ];

  protected $oCollection = null;

  protected function setUp(): void
  {
    $this->oCollection = new \Limbonia\Result\Collection($this->aData);
  }

  public function testContructor()
  {
    $this->assertTrue($this->oCollection instanceof \Limbonia\Result\Collection);
  }

  public function testGetData()
  {
    $this->assertEquals($this->aData, $this->oCollection->getData());
  }

  public function testOffsetGetDataInvalidOffset()
  {
    $this->assertFalse($this->oCollection->offsetGet(4));
  }

  public function testOffsetGetData()
  {
    $this->assertEquals($this->aData[0], $this->oCollection->offsetGet(0));
  }

  public function testGetFields()
  {
    $this->assertEquals(array_keys($this->aData[0]), $this->oCollection->getFields());
  }

  public function testGetAll()
  {
    $this->assertEquals($this->aData, $this->oCollection->getAll());
  }

  public function testOffsetExistsValidOffset()
  {
    $this->assertTrue($this->oCollection->offsetExists(0));
  }

  public function testOffsetExistsInvalidOffset()
  {
    $this->assertFalse($this->oCollection->offsetExists(4));
  }

  public function testCount()
  {
    $this->assertEquals(count($this->aData), $this->oCollection->count());
  }

  public function testCurrent()
  {
    $this->assertEquals($this->aData[0], $this->oCollection->current());
  }

//////

  public function testKey()
  {
    $this->assertEquals(0, $this->oCollection->key());
  }

  public function testNext()
  {
    //advance the internal pointer
    $this->oCollection->next();

    $this->assertEquals($this->aData[1], $this->oCollection->current());
  }

  public function testRewind()
  {
    //advance the internal pointer
    $this->oCollection->next();
    $this->oCollection->next();

    //now rewind it to the beginning...
    $this->oCollection->rewind();

    $this->assertEquals($this->aData[0], $this->oCollection->current());
  }

  public function testNotValidOnEmptyData()
  {
    $oCollection = new \Limbonia\Result\Collection();

    $this->assertFalse($oCollection->valid());
  }

  public function testNotValidOnPassedEndOfData()
  {
    $iCount = $this->oCollection->count();

    //we start on the first item, so advancing the pointer by the count should always be one off the end...
    for ($i = 0; $i < $iCount; $i++)
    {
      $this->oCollection->next();
    }

    $this->assertFalse($this->oCollection->valid());
  }

  public function testValidOnSetData()
  {
    $this->assertTrue($this->oCollection->valid());
  }

  public function testSeekOutOfBounds()
  {
    $this->expectException(\OutOfBoundsException::class);
    $this->oCollection->seek(4);
  }

  public function testSeekInBounds()
  {
    $iKey = 1;
    $this->oCollection->seek($iKey);
    $this->assertEquals($this->aData[$iKey], $this->oCollection->current());
  }
}
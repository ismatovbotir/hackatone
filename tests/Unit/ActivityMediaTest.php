<?php

namespace Tests\Unit;

use App\Support\ActivityMedia;
use PHPUnit\Framework\TestCase;

class ActivityMediaTest extends TestCase
{
    public function test_type_comes_from_the_file_content(): void
    {
        $img = imagecreatetruecolor(4, 4);
        ob_start();
        imagegif($img);
        $gif = ob_get_clean();

        $this->assertSame('gif', ActivityMedia::typeOf($gif));
        $this->assertSame('mp4', ActivityMedia::typeOf("\x00\x00\x00\x18ftypmp42\x00\x00\x00\x00mp42isom".str_repeat("\x00", 32)));
        $this->assertNull(ActivityMedia::typeOf('<?php echo "hi";'));
        $this->assertNull(ActivityMedia::typeOf('<svg xmlns="http://www.w3.org/2000/svg"></svg>'));
    }
}

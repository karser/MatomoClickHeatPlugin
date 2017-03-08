<?php
/**
 * ClickHeat : Maps generation class from a ClickHeat logfile
 *
 * @author Yvan Taviaud - Dugwood - www.dugwood.com
 * @since 12/05/2007
 * @modifier trungtran07@gmail.com 2017-03
 */
namespace Piwik\Plugins\ClickHeat\Adapter;

use Piwik\Piwik;
use Piwik\Plugins\ClickHeat\Utils\AbstractHeatmap;

class FileSystemHeatmapAdapter extends AbstractHeatmap
{
    /** @var array $files Logfiles to use */
    var $files = [];
    /** @var string $browser Browser filter */
    var $browser;
    /** @var array $layout Webpage layout */
    var $layout;
    /** @var integer $minScreen Screen filter */
    var $minScreen;
    /** @var integer $maxScreen Screen filter */
    var $maxScreen;

    /**
     * Add a file to parse and check existence
     *
     * @param string $file
     */
    public function addFile($file)
    {
        if (file_exists($file)) {
            $this->files[] = $file;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function startDrawing()
    {
        /* Add files */
        // TODO : move this to adapter
        $maxDate = strtotime($this->target->getMaxDate());
        $processingDate = strtotime($this->target->getMinDate());
        while ($processingDate < $maxDate) {
            $this->addFile($this->target->getLogPath() . $this->target->getGroupId() . '/' . date('Y-m-d', $processingDate) . '.log');
            $processingDate += 86400; // +1 day
        }

        return true;
    }

    public function drawPixels($image)
    {
        /** If it's not the first image, just use the value stored in the temp files */
        if ($image !== 0) {
            if (file_exists($this->cache . sprintf($this->file, $image) . '_log')) {
                $f = fopen($this->cache . sprintf($this->file, $image) . '_log', 'r');
                while (feof($f) === false) {
                    $click = explode('|', trim(fgets($f, 1024)));
                    if (count($click) === 3) {
                        $x = (int) $click[0];
                        $y = (int) $click[1];
                        $c = (bool) $click[2];
                        if ($this->heatmap === true) {
                            /** Apply a calculus for the step, with increases the speed of rendering: step = 3, then pixel is drawn at x = 2 (center of a 3x3 square) */
                            $x -= $x % $this->step - $this->startStep;
                            $y -= $y % $this->step - $this->startStep;
                            /** Add 1 to the current color of this pixel (color which represents the sum of clicks on this pixel) */
                            $color = imagecolorat($this->image, $x, $y) + 1;
                            imagesetpixel($this->image, $x, $y, $color);
                            $this->maxClicks = max($this->maxClicks, $color);
                        } else {
                            /** Put a red or green cross at the click location */
                            $color = $c === true ? 0xFF0000 : 0x00DC00;
                            imageline($this->image, $x - 2, $y - 2, $x + 2, $y + 2, $color);
                            imageline($this->image, $x + 2, $y - 2, $x - 2, $y + 2, $color);
                        }
                    }
                }
                fclose($f);
                unlink($this->cache . sprintf($this->file, $image) . '_log');
            }

            return true;
        }

        $leftWidth = (int) $this->layout[1];
        $centerWidth = (int) $this->layout[2];
        $rightWidth = (int) $this->layout[3];
        if ($leftWidth !== 0 && $centerWidth !== 0 && $rightWidth === 0) {
            /** Fixed left menu and fixed center ? Fall back to a fixed left menu only */
            $leftWidth += $centerWidth;
            $centerWidth = 0;
        } elseif ($leftWidth === 0 && $centerWidth !== 0 && $rightWidth !== 0) {
            /** Fixed right menu and fixed center ? Fall back to a fixed right menu only */
            $rightWidth += $centerWidth;
            $centerWidth = 0;
        } elseif ($leftWidth !== 0 && $centerWidth !== 0 && $rightWidth !== 0) {
            /** Everything is fixed ?? */
            return $this->raiseError(LANG_ERROR_FIXED);
        }
        $totalWidth = $leftWidth + $centerWidth + $rightWidth;

        for ($file = 0; $file < count($this->files); $file++) {
            /** Read clicks in the log file */
            $f = @fopen($this->files[$file], 'r');
            if ($f === false) {
                return $this->raiseError(LANG_ERROR_FILE . ': ' . $this->files[$file]);
            }

            $buffer = '';
            $count = 0;
            while (true) {
                $buffer .= fgets($f, 1024);
                /** Grouping by 1000 clicks */
                if (feof($f) === false && $count++ !== 1000) {
                    continue;
                }
                /** Do a regular match (faster and easier for large volume of data) */
                preg_match_all('~^(\d+)\|(\d+)\|(\d+)\|' . ($this->browser === 'all' ? '[a-z]+' : $this->browser) . '\|(\d)$~m', $buffer, $clicks);
                $buffer = '';

                for ($i = 0, $max = count($clicks[1]); $i < $max; $i++) {
                    $x = (int) $clicks[1][$i]; // X
                    $y = (int) $clicks[2][$i]; // Y
                    $w = (int) $clicks[3][$i]; // display width
                    $c = ($clicks[4][$i] < 3); // left click
                    /** W is not in the range of sizes, or right click for heatmap, or X is greater than screen size, the website is too large for the window, so we don't know where the click is... ignore those clicks */
                    if ($w <= $this->minScreen || $w > $this->maxScreen || $this->heatmap === true && $c === false || $x > $w) {
                        continue;
                    }
                    /** Correction of X for liquid and/or fixed layouts */
                    if ($w <= $totalWidth) {
                        /** Display's width is less than fixed content's one, X is absolute, many thanks to «rollenc» for pointing this out and this fix */
                    } elseif ($leftWidth !== 0 && $centerWidth === 0 && $rightWidth === 0) {
                        /** Left fixed menu */
                        if ($x <= $leftWidth) {
                            /** Click in the left menu: X is good */
                        } else {
                            /** Apply a percentage on the rest of the screen */
                            $x = $leftWidth + ceil(($this->width - $leftWidth) * ($x - $leftWidth) / ($w - $leftWidth));
                        }
                    } elseif ($leftWidth === 0 && $centerWidth === 0 && $rightWidth !== 0) {
                        /** Right fixed menu */
                        if ($w - $x <= $rightWidth) {
                            /** Click in the right menu: X is good, but relative to the right border */
                            $x = $this->width - ($w - $x);
                        } else {
                            /** Apply a percentage on the rest of the screen */
                            $x = ceil(($this->width - $rightWidth) * $x / ($w - $rightWidth));
                        }
                    } elseif ($leftWidth !== 0 && $centerWidth === 0 && $rightWidth !== 0) {
                        /** Left and right fixed menus */
                        if ($w - $x <= $rightWidth) {
                            /** Click in the right menu: X is good, but relative to the right border */
                            $x = $this->width - ($w - $x);
                        } elseif ($x <= $leftWidth) {
                            /** Click in the left menu: X is good */
                        } else {
                            /** Apply a percentage on the rest of the screen */
                            $x = $leftWidth + ceil(($this->width - $leftWidth - $rightWidth) * ($x - $leftWidth) / ($w - $leftWidth - $rightWidth));
                        }
                    } elseif ($leftWidth === 0 && $centerWidth !== 0 && $rightWidth === 0) {
                        /** Fixed centered content */
                        if (abs($x - $w / 2) <= $centerWidth / 2) {
                            /** Click is in the centered content */
                            $x = ($this->width - $w) / 2 + $x;
                        } elseif ($x < $w / 2) {
                            /** Click is at the left of the centered content */
                            $x = ($this->width - $centerWidth) / ($w - $centerWidth) * $x;
                        } else {
                            /** Click is at the right of the centered content */
                            $x = ($this->width + $centerWidth) / 2 + ($this->width - $centerWidth) / ($w - $centerWidth) * ($x - ($w + $centerWidth) / 2);
                        }
                    } else {
                        /** Layout 100% */
                        $x = $this->width / $w * $x;
                    }
                    $x = (int) $x;
                    $y = (int) $y;
                    if ($x < 0 || $x >= $this->width) {
                        continue;
                    }
                    if ($y >= 0 && $y < $this->height) {
                        if ($this->heatmap === true) {
                            /** Apply a calculus for the step, with increases the speed of rendering: step = 3, then pixel is drawn at x = 2 (center of a 3x3 square) */
                            $x -= $x % $this->step - $this->startStep;
                            $y -= $y % $this->step - $this->startStep;
                            /** Add 1 to the current color of this pixel (color which represents the sum of clicks on this pixel) */
                            $color = imagecolorat($this->image, $x, $y) + 1;
                            imagesetpixel($this->image, $x, $y, $color);
                            $this->maxClicks = max($this->maxClicks, $color);
                        } else {
                            /** Put a red or green cross at the click location */
                            $color = $c === true ? 0xFF0000 : 0x00DC00;
                            imageline($this->image, $x - 2, $y - 2, $x + 2, $y + 2, $color);
                            imageline($this->image, $x + 2, $y - 2, $x - 2, $y + 2, $color);
                        }
                    } else {
                        /** Append to the temporary log of this image */
                        error_log($x . '|' . ($y % $this->height) . '|' . $c . "\n", 3, $this->cache . sprintf($this->file, ceil($y / $this->height) - 1) . '_log');
                    }
                    /** Looking for the maximum height of click */
                    $this->maxY = max($y, $this->maxY);
                }
                unset($clicks);

                if ($count !== 1001) {
                    break;
                }
                $count = 0;
            }
            fclose($f);
        }

        return true;
    }

    /**
     * Do some cleaning or ending tasks (close database, reset array...)
     */
    public function finishDrawing()
    {
        $this->files = [];

        return true;
    }

    /**
     * @param $idSite
     *
     * @return mixed
     */
    public function getGroups($idSite)
    {
        $groups = [];
        $logPath = $this->target->getLogPath();
        $d = dir($logPath);
        /** Fix by Kowalikus: get the list of sites the current user has view access to */
        if (Piwik::isUserHasViewAccess($idSite) === false) {
            return false;
        }

        while (($dir = $d->read()) !== false) {
            if ($dir[0] === '.' || !is_dir($d->path . $dir)) {
                continue;
            }
            $pos = strpos($dir, ',');
            if ($pos === false) {
                continue;
            }
            $site = (int) substr($dir, 0, $pos);
            /** Fix by Kowalikus: check if current user has view access */
            if ($site !== $idSite) {
                continue;
            }
            $groups[$dir] = $pos === false ? $dir : substr($dir, $pos + 1);
        }
        $d->close();
        /** Sort groups in alphabetical order */
        ksort($groups);

        return $groups;
    }

    /**
     * @param $requestGroup
     *
     * @return mixed
     */
    /**
     * {@inheritdoc}
     */
    public function getGroupUrl($requestGroup)
    {
        // TODO made logPath available as a config from DI
        $logPath = $this->target->getLogPath();
        if (!is_dir($logPath . $requestGroup)) {
            return false;
        }
        $webPage = ['/'];
        if (file_exists($logPath . $requestGroup . '/url.txt')) {
            $f = @fopen($logPath . $requestGroup . '/url.txt', 'r');
            if ($f !== false) {
                $webPage = explode('>', trim(fgets($f, 1024)));
                fclose($f);
            }
        }

        return $webPage[0];
    }
}
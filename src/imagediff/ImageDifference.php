<?php

namespace Buuum;

class ImageDifference
{

    /**
     * @var int
     */
    protected $pixel_ration = 5;

    /**
     * @var int
     */
    private $block_check_size_w;

    /**
     * @var int
     */
    private $block_check_size_h;

    /**
     * @var array
     */
    private $array_map = [];

    /**
     * @var array
     */
    protected $coordinates = [];

    /**
     * @var int
     */
    private $count = 2;

    /**
     * @var int
     */
    private $back = 0;

    /**
     * ImageDifference constructor.
     * @param $image1
     * @param $image2
     */
    public function __construct($image1, $image2)
    {

        $this->image1 = @imagecreatefromstring(file_get_contents($image1));
        $this->image2 = @imagecreatefromstring(file_get_contents($image2));

    }

    /**
     * @param $value
     */
    public function setPixelRation($value)
    {
        $this->pixel_ration = $value;
    }

    /**
     * @return int
     */
    public function getPixelRation()
    {
        return $this->pixel_ration;
    }

    /**
     * @throws \Exception
     */
    public function compare()
    {

        $sx1 = imagesx($this->image1);
        $sy1 = imagesy($this->image1);

        if ($sx1 !== imagesx($this->image2) || $sy1 !== imagesy($this->image2)) {
            throw new \Exception("The images are not even the same size");
        }

        $block_check_size_rows = round($sx1 / $this->pixel_ration);
        $block_check_size_cols = round($sy1 / $this->pixel_ration);

        $this->block_check_size_w = $sx1 / $block_check_size_rows;
        $this->block_check_size_h = $sy1 / $block_check_size_cols;

        for ($x = 0; $x < $block_check_size_cols; $x++) {
            for ($y = 0; $y < $block_check_size_rows; $y++) {
                $this->array_map[$x][$y] = $this->check_differences($y, $x);
            }
        }

    }

    /**
     * @return array
     */
    public function getAreaCoordinates()
    {
        foreach ($this->array_map as $i => $col) {
            foreach ($col as $k => $v) {
                if ($v == 1 && $this->is_perimeter($i, $k)) {
                    $this->find_area($i, $k);
                    $this->count++;
                    return $this->getAreaCoordinates();
                }
            }
        }

        return $this->coordinates;
    }

    /**
     * @return array
     */
    public function getCoordinates()
    {
        foreach ($this->array_map as $i => $col) {
            foreach ($col as $k => $v) {
                if ($v == 1) {
                    $this->find_block($i, $k);
                    $this->count++;
                    return $this->getCoordinates();
                }
            }
        }

        return $this->coordinates;
    }

    /**
     * @return array
     */
    public function getCoordinatesRect()
    {
        $coordinates = [];
        foreach ($this->coordinates as $key => $coordinate) {
            $xx = [];
            $yy = [];
            foreach ($coordinate as $positions) {
                $xx[] = $positions[0];
                $yy[] = $positions[1];
            }
            $coordinates[$key] = [min($xx), min($yy), max($xx), max($yy)];
        }

        return $coordinates;
    }

    /**
     * @return array
     */
    public function getCoordinatesPoly()
    {
        $coordinates = [];
        foreach ($this->coordinates as $key => $coordinate) {
            $xx = [];
            foreach ($coordinate as $positions) {
                $xx[] = $positions[0];
                $xx[] = $positions[1];
            }
            $coordinates[$key] = $xx;
        }

        return $coordinates;
    }

    /**
     * @param int $radius
     * @return array
     */
    public function getCoordinatesCircle($radius = 10)
    {
        $coordinates = [];
        foreach ($this->coordinates as $key => $coordinate) {
            $xx = [];
            $yy = [];
            foreach ($coordinate as $positions) {
                $xx[] = $positions[0];
                $yy[] = $positions[1];
            }
            $coordinates[$key] = [array_sum($xx) / count($xx), array_sum($yy) / count($yy), $radius];
        }

        return $coordinates;
    }

    /**
     *
     */
    public function output()
    {
        foreach ($this->array_map as $i => $col) {
            foreach ($col as $k => $v) {
                if (!$v) {
                    echo "[0]";
                } else {
                    echo "[$v]";
                }
            }
            echo "<br>";
        }
    }

    /**
     * @param $x
     * @param $y
     */
    protected function mark_check($x, $y)
    {
        $this->back = 0;
        $this->coordinates[$this->count][] = [
            ($y * $this->pixel_ration) + $this->pixel_ration,
            ($x * $this->pixel_ration) + $this->pixel_ration
        ];
        $this->array_map[$x][$y] = $this->count;
    }

    /**
     * @param $x
     * @param $y
     * @return bool
     */
    protected function is_perimeter($x, $y)
    {
        for ($i = $x - 1; $i <= $x + 1; $i++) {
            for ($z = $y - 1; $z <= $y + 1; $z++) {
                if ($this->array_map[$i][$z] == 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param $x
     * @param $y
     */
    protected function find_area($x, $y)
    {

        if ($this->array_map[$x][$y + 1] == 1 && $this->is_perimeter($x, $y + 1)) {
            $this->mark_check($x, $y + 1);
            $this->find_area($x, $y + 1);
        } elseif ($this->array_map[$x + 1][$y] == 1 && $this->is_perimeter($x + 1, $y)) {
            $this->mark_check($x + 1, $y);
            $this->find_area($x + 1, $y);
        } elseif ($this->array_map[$x + 1][$y + 1] == 1 && $this->is_perimeter($x + 1, $y + 1)) {
            $this->mark_check($x + 1, $y + 1);
            $this->find_area($x + 1, $y + 1);
        } elseif ($this->array_map[$x][$y - 1] == 1 && $this->is_perimeter($x, $y - 1)) {
            $this->mark_check($x, $y - 1);
            $this->find_area($x, $y - 1);
        } elseif ($this->array_map[$x - 1][$y] == 1 && $this->is_perimeter($x - 1, $y)) {
            $this->mark_check($x - 1, $y);
            $this->find_area($x - 1, $y);
        } elseif ($this->array_map[$x - 1][$y - 1] == 1 && $this->is_perimeter($x - 1, $y - 1)) {
            $this->mark_check($x - 1, $y - 1);
            $this->find_area($x - 1, $y - 1);
        } else {
            $initial = $this->coordinates[$this->count][0];

            $last = $this->coordinates[$this->count][count($this->coordinates[$this->count]) - (1 + $this->back)];
            $this->back++;

            $last_x = ($last[0] - $this->pixel_ration) / $this->pixel_ration;
            $last_y = ($last[1] - $this->pixel_ration) / $this->pixel_ration;

            if (($last[0] == $initial[0] || $last[0] == $initial[0] + $this->pixel_ration || $last[0] == $initial[0] - $this->pixel_ration) && ($last[1] == $initial[1] || $last[1] == $initial[1] + $this->pixel_ration || $last[1] == $initial[1] - $this->pixel_ration)) {
                $this->mark_check($last_y, $last_x);
            } else {
                $this->find_area($last_y, $last_x);
            }
        }
    }

    /**
     * @param $x
     * @param $y
     */
    protected function find_block($x, $y)
    {
        for ($i = $x - 1; $i <= $x + 1; $i++) {
            for ($z = $y - 1; $z <= $y + 1; $z++) {
                if ($this->array_map[$i][$z] == 1) {
                    $this->mark_check($i, $z);
                    $this->find_block($i, $z);
                }
            }
        }
    }

    /**
     * @param $x_start
     * @param $y_start
     * @return bool
     */
    protected function check_differences($x_start, $y_start)
    {
        $total_x = ($this->block_check_size_w * $x_start) + $this->block_check_size_w;
        $total_y = ($this->block_check_size_h * $y_start) + $this->block_check_size_h;

        $difference = false;
        for ($x = $this->block_check_size_w * $x_start; $x < $total_x; $x++) {
            for ($y = $this->block_check_size_h * $y_start; $y < $total_y; $y++) {
                $rgb1 = imagecolorat($this->image1, $x, $y);
                $pix1 = imagecolorsforindex($this->image1, $rgb1);

                $rgb2 = imagecolorat($this->image2, $x, $y);
                $pix2 = imagecolorsforindex($this->image2, $rgb2);

                if ($pix1 !== $pix2) {
                    $difference = true;
                    break;
                }
            }
        }

        return $difference;
    }

}
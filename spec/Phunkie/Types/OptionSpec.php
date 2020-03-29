<?php

/*
 * This file is part of Phunkie, library with functional structures for PHP.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\Phunkie\Types;

use Phunkie\Cats\Show;
use function Phunkie\Functions\show\showValue;
use function Phunkie\Functions\show\usesTrait;
use function Phunkie\Functions\applicative\ap;
use function Phunkie\Functions\applicative\pure;
use function Phunkie\Functions\applicative\map2;
use function Phunkie\Functions\monad\bind;
use function Phunkie\Functions\monad\flatten;
use function Phunkie\Functions\monad\mcompose;
use Phunkie\Types\None;
use Phunkie\Types\Some;
use PhpSpec\ObjectBehavior;

use Phunkie\Ops\Option\OptionApplicativeOps;

use Md\PropertyTesting\TestTrait;
use Eris\Generator\IntegerGenerator as IntGen;
use Phunkie\Utils\WithFilter;

/**
 * @mixin OptionApplicativeOps
 */
class OptionSpec extends ObjectBehavior
{
    use TestTrait;

    function let()
    {
        $this->beAnInstanceOf(Some::class);
        $this->beConstructedThrough('instance', [1]);
    }

    function it_is_showable()
    {
        $this->shouldBeShowable();
        expect(showValue(Option(2)))->toReturn("Some(2)");
        expect(showValue(None()))->toReturn("None");
    }

    function it_is_a_functor()
    {
        $spec = $this;
        $this->forAll(
            new IntGen()
        )->then(function($a) use ($spec) {
            expect(Option($a)->map(function ($x) {
                return $x + 1;
            }))->toBeLike(Some($a + 1));
        });
    }

    function it_has_filter()
    {
        $this->filter(function($x){return $x == 1;})
            ->shouldBeLike(Some(1));
    }

    function it_has_withFilter()
    {
        $this->withFilter(function($x){return $x == 1;})
            ->shouldBeAnInstanceOf(WithFilter::class);
    }

    function its_withFilter_plus_map_to_identity_is_equivalent_to_filter()
    {
        $this->withFilter(function($x){return $x == 1;})->map(function($x) { return $x;})
            ->shouldBeLike($this->filter(function($x){return $x == 1;}));
    }

    function it_is_an_applicative()
    {
        $x = (ap (Option(function($a) { return $a +1; }))) (Option(1));
        expect($x)->toBeLike(Option(2));

        $x = (pure (Option)) (42);
        expect($x)->toBeLike(Option(42));

        $x = ((map2 (function($x, $y) { return $x + $y; })) (Option(1))) (Option(2));
        expect($x)->toBeLike(Option(3));
    }

    function it_is_a_monad()
    {
        $xs = (bind (function($a) { return Option($a +1); })) (Option(1));
        expect($xs)->toBeLike(Option(2));

        $xs = flatten (Option(Option(1)));
        expect($xs)->toBeLike(Option(1));

        $xs = Option("h");
        $f = function(string $s) { return Option($s . "e"); };
        $g = function(string $s) { return Option($s . "l"); };
        $h = function(string $s) { return Option($s . "o"); };
        $hello = mcompose($f, $g, $g, $h);
        expect($hello($xs))->toBeLike(Option("hello"));
    }

    function it_returns_none_when_none_is_mapped()
    {
        $this->beAnInstanceOf(None::class);
        $this->beConstructedThrough('instance', []);

        $this->map(function($x) { return $x + 1; })->shouldBeLike(None());
    }

    function it_has_applicative_ops()
    {
        $this->shouldBeUsing(OptionApplicativeOps::class);
    }

    function it_returns_none_when_none_is_applied()
    {
        $this->beAnInstanceOf(None::class);
        $this->beConstructedThrough('instance', []);
        $this->apply(Option(function($x) { return $x + 1; }))->shouldBeLike(None());
    }

    function it_applies_the_result_of_the_function_to_a_List()
    {
        $spec = $this;
        $this->forAll(
            new IntGen()
        )->then(function($a) use ($spec) {
            expect(Option($a)->apply(Option(function($x) { return $x + 1; })))
                ->toBeLike(Some($a + 1));
        });
    }

    function getMatchers(): array
    {
        return [
            "beUsing" => function($sus, $trait){
                return usesTrait($sus, $trait);
            },
            "beShowable" => function($sus){
                return usesTrait($sus, Show::class);
            }
        ];
    }
}
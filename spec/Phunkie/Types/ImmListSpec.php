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

use Phunkie\Cats\{Show,Traverse};
use function Phunkie\Functions\applicative\{ap,pure,map2};
use function Phunkie\Functions\monad\{bind,flatten,mcompose};
use function Phunkie\Functions\show\{showValue,usesTrait};
use function Phunkie\Functions\immlist\transpose;
use Phunkie\Ops\ImmList\ImmListApplicativeOps;
use Phunkie\Types\Nil;
use PhpSpec\ObjectBehavior;

use Md\PropertyTesting\TestTrait;
use Eris\Generator\SequenceGenerator as SeqGen;
use Eris\Generator\IntegerGenerator as IntGen;
use Phunkie\Utils\WithFilter;

/**
 * @mixin ImmListApplicativeOps
 */
class ImmListSpec extends ObjectBehavior
{
    use TestTrait;

    function let()
    {
        $this->beConstructedWith(1, 2, 3);
    }

    function it_is_showable()
    {
        $this->shouldBeShowable();
        expect(showValue(ImmList(1,2,3)))->toReturn("List(1, 2, 3)");
    }

    function it_is_a_functor()
    {
        $spec = $this;
        $this->forAll(
            new SeqGen(new IntGen())
        )->then(function($list) use ($spec) {
            expect(ImmList(...$list)->map(function ($x) {
                return $x + 1;
            }))->toBeLike(ImmList(...array_map(function($x) { return $x + 1; }, $list)));
        });
    }

    function it_returns_an_empty_list_when_an_empty_list_is_mapped()
    {
        $this->beAnInstanceOf(Nil::class);
        $this->beConstructedWith();
        $this->map(function($x) { return $x + 1; })->shouldBeEmpty();
    }

    function it_is_has_applicative_ops()
    {
        expect(usesTrait($this->getWrappedObject(), ImmListApplicativeOps::class))->toBe(true);
    }

    function it_returns_an_empty_list_when_an_empty_list_is_applied()
    {
        $this->beAnInstanceOf(Nil::class);
        $this->beConstructedWith();
        $this->apply(ImmList(function($x) { return $x + 1; }))->shouldBeEmpty();
    }

    function it_applies_the_result_of_the_function_to_a_List()
    {
        $spec = $this;
        $this->forAll(
            new SeqGen(new IntGen())
        )->then(function($list) use ($spec) {
            expect(ImmList(...$list)->apply(ImmList(function($x) { return $x + 1; })))
                ->toBeLike(ImmList(...array_map(function($x) { return $x + 1; }, $list)));
        });
    }

    function it_returns_its_length()
    {
        $this->beConstructedWith(1, 2, 3);
        $this->length->shouldBe(3);
    }

    function it_has_filter()
    {
        $this->beConstructedWith(1, 2, 3);
        $this->filter(function($x){return $x == 2;})->shouldBeLike(ImmList(2));
    }

    function it_has_withFilter()
    {
        $this->beConstructedWith(1, 2, 3);
        $this->withFilter(function($x){return $x == 2;})
            ->shouldBeAnInstanceOf(WithFilter::class);
    }

    function its_withFilter_plus_map_to_identity_is_equivalent_to_filter()
    {
        $this->beConstructedWith(1, 2, 3);
        $this->withFilter(function($x){return $x == 2;})->map(function($x) { return $x;})
            ->shouldBeLike($this->filter(function($x){return $x == 2;}));
    }

    function it_has_reject()
    {
        $this->beConstructedWith(1, 2, 3);
        $this->reject(function($x){return $x == 2;})->shouldBeLike(ImmList(1, 3));
    }

    function it_implements_reduce()
    {
        $this->beConstructedWith(1, 2, 3);
        $this->reduce(function($x, $y){return $x  + $y;})->shouldBe(6);
    }

    function it_implements_reduce_string_example()
    {
        $this->beConstructedWith("a", "b", "c");
        $this->reduce(function($x, $y){return $x  . $y;})->shouldBe("abc");
    }

    function it_will_complain_if_reduce_returns_a_type_different_to_the_list_type()
    {
        $this->beConstructedWith(1,2,3);
        $this->shouldThrow()->duringReduce(function($x, $y){return "Oh no! a string!";});
    }

    function it_can_be_casted_to_array()
    {
        $this->beConstructedWith(1,2,3);
        $this->toArray()->shouldBe([1,2,3]);
    }
    
    function it_can_be_transposed()
    {
        $this->beConstructedWith(ImmList(1,2,3), ImmList(4,5,6));
        $transposed = ImmList(
            ImmList(1, 4),
            ImmList(2, 5),
            ImmList(3, 6)
        );
        $this->transpose()->shouldBeLike($transposed);
        expect(transpose(ImmList(ImmList(1,2,3), ImmList(4,5,6))))->toBeLike($transposed);
    }

    function it_zips()
    {
        $this->beConstructedWith(1,2,3);
        $this->zip(ImmList("A", "B", "C"))->shouldBeLike(
            ImmList(Pair(1,"A"), Pair(2,"B"), Pair(3,"C"))
        );
    }

    function it_takes_n_elements_from_list()
    {
        $this->beConstructedWith(1,2,3);
        $this->take(2)->shouldBeLike(ImmList(1,2));
    }

    function it_takes_while_something_is_true()
    {
        $this->beConstructedWith(1,2,3,4,5,6);
        $this->takeWhile(function($el) { return $el < 4; })->shouldBeLike(ImmList(1,2,3));
        $this->takeWhile(function($el) { return $el < 9; })->shouldBeLike(ImmList(1,2,3,4,5,6));
        $this->takeWhile(function($el) { return $el < 0; })->shouldBeLike(ImmList());
    }

    function it_drops_while_something_is_true()
    {
        $this->beConstructedWith(1,2,3,4,5,6);
        $this->dropWhile(function($el) { return $el < 4; })->shouldBeLike(ImmList(4,5,6));
        $this->dropWhile(function($el) { return $el < 9; })->shouldBeLike(ImmList());
        $this->dropWhile(function($el) { return $el < 0; })->shouldBeLike(ImmList(1,2,3,4,5,6));
    }

    function it_drops_n_elements_from_list()
    {
        $this->beConstructedWith(1,2,3);
        $this->drop(2)->shouldBeLike(ImmList(3));
    }

    function it_implements_head()
    {
        $this->beConstructedWith(1,2,3);
        $this->head->shouldBe(1);
    }

    function it_implements_tail()
    {
        $this->beConstructedWith(1,2,3);
        $this->tail->shouldBeLike(ImmList(2,3));
    }

    function it_implements_init()
    {
        $this->beConstructedWith(1,2,3);
        $this->init->shouldBeLike(ImmList(1,2));
    }

    function it_implements_last()
    {
        $this->beConstructedWith(1,2,3);
        $this->last->shouldBe(3);
    }

    function it_implements_shortcut_for_mapping_over_class_members()
    {
        $_ = underscore();
        $this->beConstructedWith(new User("John"), new User("Alice"));
        $this->map($_->name)->map("strtoupper")->shouldBeLike(ImmList("JOHN", "ALICE"));
    }

    function it_is_an_applicative()
    {
        $xs = (ap (ImmList(function($a) { return $a +1; }))) (ImmList(1));
        expect($xs)->toBeLike(ImmList(2));

        $xs = (pure (ImmList)) (42);
        expect($xs)->toBeLike(ImmList(42));

        $xs = ((map2 (function($x, $y) { return $x + $y; })) (ImmList(1))) (ImmList(2));
        expect($xs)->toBeLike(ImmList(3));
    }

    function it_is_a_monad()
    {
        $xs = (bind (function($a) { return ImmList($a +1); })) (ImmList(1));
        expect($xs)->toBeLike(ImmList(2));

        $xs = flatten (ImmList(ImmList(1)));
        expect($xs)->toBeLike(ImmList(1));

        $xs = flatten (ImmList(ImmList(1), ImmList(2)));
        expect($xs)->toBeLike(ImmList(1, 2));

        $xs = ImmList("h");
        $f = function(string $s) { return ImmList($s . "e"); };
        $g = function(string $s) { return ImmList($s . "l"); };
        $h = function(string $s) { return ImmList($s . "o"); };
        $hello = mcompose($f, $g, $g, $h);
        expect($hello($xs))->toBeLike(ImmList("hello"));
    }

    function it_is_a_traverse()
    {
        $this->beConstructedWith(1,2,3);
        $this->shouldHaveType(Traverse::class);

        $this->traverse(function($x) { return Option($x); })
            ->shouldBeLike(Some(ImmList(1,2,3)));

        $this->traverse(function($x) { return $x > 2 ? None() : Some($x); })
            ->shouldBeLike(None());
    }

    function it_implements_sequence()
    {
        $this->beConstructedWith(Some(1), Some(2), Some(3));
        $this->sequence()->shouldBeLike(Some(ImmList(1,2,3)));
    }

    function it_returns_None_if_any_value_in_sequence_is_None()
    {
        $this->beConstructedWith(Some(1), None(), Some(3));
        $this->sequence()->shouldBeLike(None());
    }

    function getMatchers(): array
    {
        return ["beShowable" => function($sus){
            return usesTrait($sus, Show::class);
        }];
    }
}

class User {
    public $name;
    public function __construct($name)
    {
        $this->name = $name;
    }
}
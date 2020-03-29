<?php

namespace spec\Phunkie\Cats;

use Md\PropertyTesting\TestTrait;
use Phunkie\Laws\FunctorLaws;
use Phunkie\Types\Function1;
use Md\PropertyTesting\Generator\RandomKindGenerator;
use PhpSpec\ObjectBehavior;

class FunctorSpec extends ObjectBehavior
{
    use FunctorLaws,TestTrait,RandomKindGenerator;

    function it_obeys_the_identity_law_of_covariance()
    {
        $this->forAll(
            $this->genRandomFA()
        )->then(function($fa) {
            $arg = $fa instanceof Function1 ? Some(42) : None();
            expect($this->covariantIdentity($fa, $arg))->toBe(true);
        });
    }

    function it_obeys_the_composition_law_of_covariance()
    {
        $this->forAll(
            $this->genRandomFA(),
            $this->genFunctionStringToInt(),
            $this->genFunctionStringToInt()
        )->then(function($fa, $f, $g) {
            expect($this->covariantComposition($fa, $f, $g))->toBe(true);
        });
    }
}
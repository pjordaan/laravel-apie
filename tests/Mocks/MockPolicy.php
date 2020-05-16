<?php


namespace W2w\Laravel\Apie\Tests\Mocks;


class MockPolicy
{
    public function create($user, DomainObjectThatRequireAuthorization $object) {
        return strlen($object->id) === 1;
    }

    public function update($user, DomainObjectThatRequireAuthorization $object) {
        return strlen($object->id) === 2;
    }

    public function view($user, DomainObjectThatRequireAuthorization $object) {
        return in_array(strlen($object->id), [1, 2]);
    }
}

#!/bin/bash

__DIR__=$(cd `dirname $0`; pwd)/../

$__DIR__/bin/imi-cli generate/model "app\Model" --app-namespace "app" --prefix=tb_ --override=base --lengthCheck --sqlSingleLine

$__DIR__/../vendor/bin/php-cs-fixer fix

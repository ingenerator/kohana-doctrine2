kohana-doctrine2 - integrates [Doctrine 2 ORM](http://www.doctrine-project.org/projects/orm.html) with Kohana
=============================================================================================================

kohana-doctrine2 adds the Doctrine2 ORM library to a Kohana project, aiming to make the most of the Kohana framework
(including the cascading file system and autoloader) while maintaining clean application and module code. This module is
opinionated, and does not seek to make every part of Doctrine2 available or configurable.

In particular:

* Only PHP annotation entity mapping is supported

## Installing the basic library

Include in your composer.json:

```json
{
	"require": {
		"ingenerator/kohana-doctrine2" : "dev-master"
	}
}
```

Run composer to install the module and the external dependencies into your project with `composer install`.

You should see this module in your modules directory, with Doctrine2 and its dependencies installed into your
vendor directory in the root of your project. You should ensure your .gitignore file excludes libraries installed
to vendor with Composer.


## License

Copyright (c) 2013, inGenerator Ltd
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided
that the following conditions are met:

* Redistributions of source code must retain the above copyright notice, this list of conditions and
  the following disclaimer.
* Redistributions in binary form must reproduce the above copyright notice, this list of conditions
  and the following disclaimer in the documentation and/or other materials provided with the distribution.
* Neither the name of inGenerator Ltd nor the names of its contributors may be used to endorse or
  promote products derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR
IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS
BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR
BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

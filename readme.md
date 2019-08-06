## CommandLock

CommandLock is a helper Trait for preventing a command from being ran multiple times at once by creating a lock file for each command.

The lock files also contain process id of the command which created the lock.
If there is an attempt to run a command that is locked but the process id saved in the lock file doesn't belong to a running process, the lock is reset.

Class using this Trait also needs to have a property commandLockPathProvider of type CommandLockPathProvider accessible by the Trait.

CommandLock only runs on Unix and has no required dependencies. However only if Nette SafeStream is registered (either automatically or manually),
atomicity and thread safety of lock and unlock operations can be guaranteed.

## Installation

```composer require adt/utils```
 
## Dependencies

- ADT\Utils\Guzzle => guzzlehttp/guzzle:^6.3
- ADT\Utils\ResultSet => kdyby/doctrine:^3.2

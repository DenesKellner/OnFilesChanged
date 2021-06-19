# OnFilesChanged
A tiny tool that watches for file changes and executes a command when it happens

## Why?

Because it's a need that keeps coming up. Whether you want to implement your own SASS/LESS precompiler, or just want
to reload your localhost page when you change something, even if you just want to keep a second copy of any set of
documents - this little guy is there for you.

## How?

It just does what most similar tools do: polls the filesystem. Thanks to caching and the growing market share of SSDs,
today you literally won't feel a thing even if it's a few hundred files - but it rarely will be, I guess. You can fine
tune this tool to the actual file patterns that are likely to change, and there you go.

- Multiple file patterns are supported
- You can give it a little rest between the polls
- You can specify a settledown time after changes

## What's settledown time?

When a file changes, it's rarely a good idea to do something right away; maybe it is, you decide, but it's very
efficient to only react on changes when they're *settled*, that is, a few seconds passed since the save button. If you
don't use this option, reaction will be almost immediate - depending on the *wait* option, but as soon as the file is
changed, action will follow.

## Some examples

The simplest thing to do is make a short beep when a file changes:

```text
d:\projects\demo1> onfileschanged beep.bat *.css *.js *.php --new-process
```

Now in projects/demo1, whenever a `php`/`js`/`css` file changes, `beep.bat` will be launched in a new process, and
since it's nothing but a chr(7) and an exit, the command window will only briefly flash up, then disappear again;
but you'll hear the beep.

Here's an example that makes a bit more sense:

```text
d:\projects\demo1> onfileschanged less-compiler.bat *.less
```
Now every time you change any of your `.less` files, that little batch of yours will be launched (hopefully you have
a .less compiler and you know how to run it from a batch), so you don't have to do it manually. Even better, if you
do it like this:

```text
d:\projects\demo1> onfileschanged less-compiler.bat *.less --settledown 10
```
This time, whenever a change happens, `onfileschanged` will count down from 10 and only launch the command if there
are no further changes in this time period. If you're like me, hitting *Save* quite often during work, your CPU will
thank you for not having to work every time.




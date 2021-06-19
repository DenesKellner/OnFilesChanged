# OnFilesChanged
A tiny tool that watches for file changes and executes a command when it happens

## Why?

Because it's a need that keeps coming up. Whether you want to implement your own SASS/LESS precompiler, or just want to reload your localhost page when you change something, even if you just want to keep a second copy of any set of documents - this little guy is there for you.

## How?

It just does what most similar tools do: polls the filesystem. Thanks to caching and the growing market share of SSDs, today you literally won't feel a thing even if it's a few hundred files - but it rarely will be, I guess. You can fine tune this tool to the actual file patterns that are likely to change, and there you go.

- Multiple file patterns are supported
- You can give it a little rest between the polls
- You can specify a settledown time after changes

## What's settledown time?

When a file changes, it's rarely a good idea to do something right away; maybe it is, you decide, but it's very efficient to only react on changes when they're *settled*, that is, a few seconds passed since the save button. If you don't use this option, reaction will be almost immediate - depending on the *wait* option, but as soon as the file is changed, action will follow.

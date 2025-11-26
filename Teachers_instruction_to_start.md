# Software Tooling

You need 4 programmes in order to get your project up and running.

# Herd - Local development environment

The free version of [Laravel Herd](https://herd.laravel.com/) suffices, available for Windows & MacOs). When you install Herd, you get **NGINX** (webserver), **PHP** (the programming language) & **sqlite** (database). 

<aside>
💡

As Herd is optimized for Laravel, it also allows you to create a new project from within the sites menu as well. But… don’t do that

</aside>

## 🩺 First Aid

- **I don’t see the herd Icon in the menu bar (Mac)**
    
    Likely related to too many icons in the menubar - macOS simply hides some of them and there's no configuration for that. 
    
    Many people use **Bartender 5** as macOS tool. As quick fix, your students can quit apps from the menubar that they don't use or create sites via the command line.
    
- **I want try a full reinstall - how to do (Mac)?**
    1. Uninstalling: https://herd.laravel.com/docs/macos/troubleshooting/uninstalling
    2. Reboot your laptop
    3. Reinstall 

# IDE - integrated development environment (editor)

You will write code. This is typically done in an editor or IDE. Install the IDE of your preference (e.g. [PhpStorm](https://www.jetbrains.com/phpstorm/) or [VScode](https://code.visualstudio.com/) - both have a a free (student) version). 

<aside>
💡

Both IDEs have a plugin that make working with a Laravel codebase easier. They are free to install, so don’t miss out on them:

- PhpStorm: https://plugins.jetbrains.com/plugin/13441-laravel-idea
- VScode: https://marketplace.visualstudio.com/items?itemName=laravel.vscode-laravel
</aside>

# Git

Git is used for your code management. While it works perfectly fine to only run it locally, I recommend to connect it to Github, so that your code is backed up. Install [Github Desktop](https://github.com/apps/desktop) and create a (free) [Github account](https://www.notion.so/a87a3664088045c8a423f3c91bee2868?pvs=21).

- How to **initialize your git repository**
    
    See class recording
    
- How to **connect** your local repository **with Github**
    
    See class recording
    

# Database client

With Laravel Herd comes the Sqlite database driver. This is the most lightweight database you can have. It’s often handy to have a programme to inspect the data in the database manually. For this we use a database client. I recommend [Heidisql](https://www.heidisql.com/) (Windows) and [DB Browser](https://sqlitebrowser.org/) (MacOs). 

⚠️ For MacOs, don’t use Tableplus, which is more famous, but doesn’t offer a good free plan.

# Tinkerwell - the code scratchpad/runner

This is an **optional** programme to install, you don’t really need it but it is very handy to quickly do some tests and check out things.

More info on [Tinkerwell](https://tinkerwell.app/), for a free license: [add your @code.berlin email to your profile](https://tinkerwell.app/education) (info behind the link).
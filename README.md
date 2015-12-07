Steps to Install
Type this in terminal/cmd

Go to the project folder

1. cd crawler
2. php parse_quantcast.php > urllist.txt   
2. cat urllist.txt | xargs -L1 -P32 php crawler.php
3. cd ..
4. php add.php 
5. go to /search.php?q=kitchen

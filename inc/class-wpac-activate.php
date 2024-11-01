<?php

/* Fired during plugin activation */
class WPAC_Activate
{
	public static function activate()
	{
		// check if we are dealing with fresh installation
		if ( ! get_option( 'wpac_version' ) )
		{
			// insert demo data to the database
			self::insert_demo_data();

			// save current version in database
			update_option('wpac_version', WPAC_VERSION);
		}
	}

	// -------------------------------------------------------------------

	public static function insert_demo_data()
	{

		$authors = 'Amelia
Lily
Emily
Sophia
Isabelle
Sophie
Olivia
Jessica
Chloe
Mia
Isla
Isabella
Ava
Charlotte
Grace
Evie
Poppy
Lucy
Ella
Holly
Emma
Molly
Annabelle
Erin
Freya
Ruby
Scarlett
Alice
Layla
Hannah
Eva
Imogen
Millie
Daisy
Abigail
Amy
Zoe
Megan
Maisie
Phoebe
Maya
Anna
Eliza
Caitlin
Amelie
Jasmine
Florence
Sienna
Madison
Eleanor
Darcey
Lola
Elizabeth
Leah
Matilda
Summer
Elsie
Ellie
Zara
Rosie
Kayla
Esme
Amber
Georgia
Bethany
Rose
Evelyn
Lexi
Niamh
Katie
Alyssa
Lauren
Heidi
Gracie
Skye
Willow
Faith
Beth
Alexandra
Iris
Harriet
Violet
Lara
Martha
Rebecca
Seren
Gabriella
Tilly
Naomi
Sarah
Clara
Nicole
Elise
Mila
Annie
Sara
Bella
Francesca
Elena
Libby
Harry
Jack
Oliver
Charlie
James
George
Thomas
Ethan
Jacob
William
Daniel
Joshua
Max
Noah
Alfie
Samuel
Dylan
Oscar
Lucas
Aidan
Isaac
Riley
Henry
Benjamin
Joseph
Alexander
Lewis
Leo
Tyler
Jayden
Zac
Freddie
Archie
Logan
Adam
Ryan
Nathan
Matthew
Sebastian
Jake
Toby
Alex
Luke
Liam
Harrison
David
Jamie
Edward
Luca
Elliot
Aaron
Finley
Michael
Zachary
Mason
Sam
Muhammad
Connor
Ben
Reuben
Theo
Rhys
Arthur
Caleb
Dexter
Rory
Jenson
Evan
Gabriel
Ewan
Callum
Seth
Felix
Austin
Owen
Leon
Cameron
Jude
Harley
Blake
Harvey
Tom
Hugo
Finn
Bobby
Hayden
Kyle
Jasper
Tommy
Eli
Kian
Andrew
John
Louie
Dominic
Joe
Elijah
Kai
Frankie
Stanley';

		update_option( 'wpa_comments_authors', $authors );

$comments = 'This is great!
WOW, very helpfull!
Congrats!! Enjoy so much data that is actually fascinating and instructional, this site!! Truly pleased I found it!!
This article may be worth everyone’s interest. When can I find more out?
Wonderful submit, very informative. I wonder why the opposite specialists of this sector do not realize this. You must continue your writing. I’m sure, you have a great readers’ base already!
Hi, i think that i saw you visited my blog so i came to “return the favor”. I am attempting to find things to enhance my website! I suppose its ok to use some of your ideas!!
Loving the information on this web site, you have done outstanding job on the blog posts.
I like the helpful information you provide in your articles. I will bookmark your blog and check again here regularly. I’m quite certain I will learn many new stuff right here! Best of luck for the next!
The little party of strangers now followed the Prince across a few more of the glass bridges and along several paths until they came to a garden enclosed by a high hedge.
Wow, this post is fastidious, my sister is analyzing these things, therefore I am going to convey her.
I’m not sure why but this blog is loading incredibly slow for me. Is anyone else having this problem or is it a issue on my end? I’ll check back later on and see if the problem still exists.
Hi, i think that i saw you visited my blog so i came to return the desire?.I’m trying to find things to enhance my web site!I assume its good enough to use some of your concepts!!
Hey very nice blog!
Love the site– very user pleasant and lots to see! I’m thoroughly enjoying your blog. I too am an aspiring blog blogger but I’m still new to the whole thing. Do you have any points for first-time blog writers? I’d really appreciate it.';

		update_option( 'wpa_comments_comments', $comments );
	}

}



.PHONY: all twig

all: twig


twig:
	cd lib/twig/ext/twig && phpize && ./configure && make && make install

sitemap-dev-php74-up:
	sudo UID=${UID} GID=${UID} docker-compose -f sitemap-dev-php7.4/docker-compose.yml up -d
sitemap-dev-php74-stop:
	sudo docker-compose -f sitemap-dev-php7.4/docker-compose.yml stop
sitemap-dev-php74-install:
	sudo ln -sf /src/administrator/components/com_schuweb_sitemap ${CURDIR}/sitemap-dev-php7.4/public/administrator/components/
	sudo ln -sf /src/administrator/components/com_schuweb_sitemap/language/en-GB/en-GB.com_schuweb_sitemap.ini ${CURDIR}/sitemap-dev-php7.4/public/administrator/language/en-GB/
	sudo ln -sf /src/administrator/components/com_schuweb_sitemap/language/en-GB/en-GB.com_schuweb_sitemap.sys.ini ${CURDIR}/sitemap-dev-php7.4/public/administrator/language/en-GB/
	sudo ln -sf /src/components/com_schuweb_sitemap ${CURDIR}/sitemap-dev-php7.4/public/components/
	sudo ln -sf /src/components/com_schuweb_sitemap/language/en-GB/en-GB.com_schuweb_sitemap.ini ${CURDIR}/sitemap-dev-php7.4/public/language/en-GB/
	sudo ln -sf /src/components/com_schuweb_sitemap/language/en-GB/en-GB.com_schuweb_sitemap.sys.ini ${CURDIR}/sitemap-dev-php7.4/public/language/en-GB/
	sudo ln -sf /src/plugins/schuweb_sitemap ${CURDIR}/sitemap-dev-php7.4/public/plugins/
	sudo ln -sf /src/plugins/schuweb_sitemap/com_content/language/en-GB.plg_schuweb_sitemap_com_content.ini ${CURDIR}/sitemap-dev-php7.4/public/administrator/language/en-GB/
	sudo ln -sf /src/plugins/schuweb_sitemap/com_k2/language/en-GB.plg_schuweb_sitemap_com_k2.ini ${CURDIR}/sitemap-dev-php7.4/public/administrator/language/en-GB/
	sudo ln -sf /src/plugins/schuweb_sitemap/com_kunena/language/en-GB.plg_schuweb_sitemap_com_kunena.ini ${CURDIR}/sitemap-dev-php7.4/public/administrator/language/en-GB/
	sudo ln -sf /src/plugins/schuweb_sitemap/com_sobipro/language/en-GB.plg_schuweb_sitemap_com_sobipro.ini ${CURDIR}/sitemap-dev-php7.4/public/administrator/language/en-GB/
	sudo ln -sf /src/plugins/schuweb_sitemap/com_virtuemart/language/en-GB.plg_schuweb_sitemap_com_virtuemart.ini ${CURDIR}/sitemap-dev-php7.4/public/administrator/language/en-GB/
	sudo ln -sf /src/plugins/schuweb_sitemap/com_weblinks/language/en-GB.plg_schuweb_sitemap_com_weblinks.ini ${CURDIR}/sitemap-dev-php7.4/public/administrator/language/en-GB/
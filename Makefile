.PHONY: zip

zip:
	rm ./MatomoClickHeatPlugin.zip || true
	cd .. && zip -rqq ./MatomoClickHeatPlugin/MatomoClickHeatPlugin.zip \
		./MatomoClickHeatPlugin/ \
		-x "*.git*" -x "*.idea*" -x "MatomoClickHeatPlugin.zip"

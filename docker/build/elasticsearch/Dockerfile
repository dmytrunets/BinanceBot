  elasticsearch:
    image: docker.elastic.co/elasticsearch/elasticsearch:${TAG}
    container_name: elasticsearch
    secrets:
      - source: ca.crt
        target: /usr/share/elasticsearch/config/certs/ca/ca.crt
      - source: elasticsearch.yml
        target: /usr/share/elasticsearch/config/elasticsearch.yml
      - source: elasticsearch.keystore
        target: /usr/share/elasticsearch/config/elasticsearch.keystore
      - source: elasticsearch.key
        target: /usr/share/elasticsearch/config/certs/elasticsearch/elasticsearch.key
      - source: elasticsearch.crt
        target: /usr/share/elasticsearch/config/certs/elasticsearch/elasticsearch.crt
    ports: ['9200:9200']
    networks: ['stack']
    volumes:
      - 'es_data:/usr/share/elasticsearch/data'
      - './scripts/setup-users.sh:/usr/local/bin/setup-users.sh:ro'
    healthcheck:
      test: curl --cacert /usr/share/elasticsearch/config/certs/ca/ca.crt -s https://localhost:9200 >/dev/null; if [[ $$? == 52 ]]; then echo 0; else echo 1; fi
      interval: 30s
      timeout: 10s
      retries: 5

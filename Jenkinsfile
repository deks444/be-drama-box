pipeline {
    agent any

    options {
        buildDiscarder(logRotator(numToKeepStr: '5'))
        timeout(time: 15, unit: 'MINUTES')
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Setup Runtime & .env') {
            steps {
                withCredentials([file(credentialsId: 'dramabox-auth-env', variable: 'SECRET_ENV')]) {
                    script {
                        // 1. Setup .env
                        sh 'rm -rf .env'
                        sh 'cat "${SECRET_ENV}" > .env'

                        // 2. Cari link download terbaru secara dinamis agar tidak 404
                        sh '''
                            mkdir -p local_bin
                            echo "Mencari versi PHP terbaru..."
                            
                            # Mengambil URL download terbaru dari GitHub API
                            LATEST_URL=$(curl -sk https://api.github.com/repos/crazywhalecc/static-php-cli/releases/latest | grep "browser_download_url" | grep "php-8.2" | grep "cli-linux-x86_64.tar.gz" | cut -d '"' -f 4 | head -n 1)

                            if [ -z "$LATEST_URL" ]; then
                                echo "Gagal mendapatkan link otomatis, mencoba link fallback stabil..."
                                LATEST_URL="https://github.com/crazywhalecc/static-php-cli/releases/download/v1.7.0/php-8.2.20-cli-linux-x86_64.tar.gz"
                            fi

                            echo "Downloading dari: $LATEST_URL"
                            curl -Lk "$LATEST_URL" -o php.tar.gz
                            
                            tar -xzf php.tar.gz -C local_bin/
                            chmod +x local_bin/php
                            ./local_bin/php -v
                        '''
                    }
                }
            }
        }

        stage('Install Dependencies') {
            steps {
                sh '''
                    # Selalu ambil composer terbaru
                    curl -Lk https://getcomposer.org/download/latest-stable/composer.phar -o composer.phar
                    ./local_bin/php composer.phar install --no-interaction --prefer-dist --optimize-autoloader --no-dev --ignore-platform-reqs
                '''
            }
        }

        stage('Laravel Finalize') {
            steps {
                sh '''
                    ./local_bin/php artisan key:generate --force
                    ./local_bin/php artisan storage:link
                    ./local_bin/php artisan migrate --force || echo "Migrasi gagal/skip."
                    ./local_bin/php artisan config:cache
                    ./local_bin/php artisan route:cache
                    chmod -R 775 storage bootstrap/cache || true
                '''
            }
        }
    }

    post {
        always {
            sh 'rm -f php.tar.gz composer.phar'
        }
    }
}

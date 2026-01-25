pipeline {
    agent any

    options {
        buildDiscarder(logRotator(numToKeepStr: '5'))
        timeout(time: 15, unit: 'MINUTES')
        disableConcurrentBuilds()
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Setup PHP 8.2.27 & Environment') {
            steps {
                withCredentials([file(credentialsId: 'dramabox-auth-env', variable: 'SECRET_ENV')]) {
                    script {
                        sh 'rm -rf .env'
                        sh 'cat "${SECRET_ENV}" > .env'

                        sh '''
                            mkdir -p local_bin
                            URL="https://dl.static-php.dev/static-php-cli/common/php-8.2.27-cli-linux-x86_64.tar.gz"
                            curl -Lk "$URL" -o php.tar.gz
                            tar -xzf php.tar.gz -C local_bin/
                            find local_bin -name "php*" -type f -exec mv {} local_bin/php \\;
                            chmod +x local_bin/php
                        '''
                    }
                }
            }
        }

        stage('Install Dependencies') {
            steps {
                sh '''
                    curl -Lk https://getcomposer.org/composer.phar -o composer.phar
                    ./local_bin/php composer.phar install --no-interaction --prefer-dist --optimize-autoloader --no-dev --ignore-platform-reqs
                '''
            }
        }

        stage('Laravel Prep & Migrate') {
            steps {
                sh '''
                    ./local_bin/php artisan key:generate --force
                    ./local_bin/php artisan storage:link
                    ./local_bin/php artisan migrate --force || echo "Migrasi dilewati."
                    ./local_bin/php artisan config:cache
                '''
            }
        }

        stage('Run Server on Port 9004') {
            steps {
                echo 'Memulai Laravel Server di port 9004...'
                script {
                    // Menghentikan proses yang mungkin berjalan di port 9004 sebelumnya
                    sh 'fuser -k 9004/tcp || true'
                    
                    // Menjalankan Laravel server di background
                    // JENKINS_NODE_COOKIE=dontKillMe penting agar proses tidak dimatikan saat pipeline selesai
                    sh '''
                        export JENKINS_NODE_COOKIE=dontKillMe
                        nohup ./local_bin/php artisan serve --host=0.0.0.0 --port=9004 > laravel_log.txt 2>&1 &
                    '''
                    echo 'Aplikasi berjalan di http://localhost:9004'
                }
            }
        }
    }

    post {
        success {
            echo '==================================================='
            echo ' DEPLOY BERHASIL & BERJALAN DI PORT 9004           '
            echo '==================================================='
        }
    }
}
